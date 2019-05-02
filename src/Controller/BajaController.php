<?php

namespace App\Controller;

use App\Entity\ProcessStatus;
use App\Entity\Queue;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use JMS\Serializer\Expression\ExpressionEvaluator;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Facebook\WebDriver\Chrome\ChromeOptions;

/**
 * Brand controller.
 *
 * @Route("/baja")
 */
class BajaController extends Controller
{
    /**
     * Eliminar baja de la cola.
     * @FOSRest\Delete("/{bajaId}")
     */
    public function delBajaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:Baja', 'a')
            ->where('a.id = :bajaId')
            ->setParameter('bajaId', $request->get("bajaId"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
        if ($operation != null) {
            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $operation->getStatus()]);
            if ($status != null && ($status->getStatus() == "AWAITING" || $status->getStatus() == "STOPPED")) {
                $rmStatus = $em->getRepository("App:ProcessStatus")->findOneBy(['status' => "REMOVED"]);
                $operation->setStatus($rmStatus->getId());
                $operation->setProcessTime(0);
                $queueOperation = $em->createQueryBuilder()->select(array('q'))
                    ->from('App:Queue', 'q')
                    ->where('q.referenceId = :refId')
                    ->setParameter('refId', $operation->getId())
                    ->orderBy('q.id', 'DESC')
                    ->getQuery()
                    ->getOneOrNullResult();
                if(null !== $queueOperation)
                {
                    $em->remove($queueOperation);
                    $success = "true";
                }
                else
                {
                    $success = "false";
                }
                $em->flush();
            } else {
                $success = "false";
            }
            return $this->container->get("response")->success("DELETE_STATUS", $success);
        }
        {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Ver el estado de una baja.
     * @FOSRest\Get("/{bajaId}")
     */
    public function getBajaAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $baja = $qb->select(array('a'))
            ->from('App:Baja', 'a')
            ->where('a.id = :bajaId')
            ->setParameter('bajaId', $request->get("bajaId"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($baja != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $baja->getStatus()]);
            return $this->container->get("response")->success($status->getStatus(), $baja->getErrMsg());
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Crear baja.
     * @FOSRest\Post()
     */
    public function bajaAction(Request $request)
    {
        
        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Baja.
             */
            $baja = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\Baja', 'json');
            $validationErrors = $this->get('validator')->validate($baja);
            if (count($validationErrors) > 0) {
                return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
            }

            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $baja->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $baja->getCca()]));

            $baja->setDateInit();
            $baja->setProcessTime(0);
            /*
             * Paseo del tipo de identificación en caso de que sea necesario.
             */
            if ($baja->getIpt() == 6) {
                $baja->setIpf("0" . $baja->getIpf());
            }

            /*
             * La primera comprobación es básica: La baja no puede sobrepasar 60 días posteriores
             * al actual.
             * Además, la fecha no puede ser anterior a la actual.
             */
            $limitDate = (new \DateTime("now"))->modify('+60 days');
            if ($limitDate < $baja->getFrb()) {
                return $this->container->get("response")->error(400, "DATE_EXPIRE_INVALID");
            }
            if ($baja->getFrb() > (new \DateTime("now"))->modify('+3 days')) {
                return $this->container->get("response")->error(400, "DATE_PASSED");
            }

            if ($baja->getFfv() !== null && $baja->getFfv() < (new \DateTime("now"))) {
                return $this->container->get("response")->error(400, "DATE_EXPIRE_INVALID");
            }
            /*
             * Validar el tipo de empresa.
             */
            if ($baja->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Validar situación. Si no introdujo nada, el valor por defecto es 93.
             */
            if ($baja->getSit() === null) {
                $baja->setSit(93);
            }

            /*
             * Comprobar que no exista una solicitud similar y esté pendiente. (IPF + NAF)
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('a'))
                ->from('App:Baja', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.ipf = :ipf")
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('ipf', $baja->getIpf())
                ->setParameter('naf', $baja->getNaf())
                ->orderBy('a.dateProcessed', 'DESC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($task != null) {
                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

                /* Devolver resultado */
                return $this->container->get("response")->success("RETRIEVED", $task->getId());
            } else {
                /* Agregar baja */
                $baja->setDateProcessed();
                $baja->setStatus(4);
                $em->persist($baja);
                $em->flush();

                /* Agregar baja a la cola */
                $queue = new Queue();
                $queue->setReferenceId($baja->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'BAJA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("Baja", $baja->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $baja->getId());
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, "UNCAUGHT_EXCEPTION");
        }
    }
}