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
use FOS\RestBundle\Request\ParamFetcher;
use React\EventLoop\Factory;
use React\Stream\WritableResourceStream;
/**
 * Brand controller.
 *
 * @Route("/alta")
 */
class AltaController extends Controller
{
    /**
     * Eliminar alta de la cola.
     * @FOSRest\Delete("/{altaId}")
     */
    public function delAltaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:Alta', 'a')
            ->where('a.id = :altaId')
            ->setParameter('altaId', $request->get("altaId"))
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
     * Consultar alta.
     * @FOSRest\Get("/{altaId}")
     */
    public function getAltaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $alta = $qb->select(array('a'))
            ->from('App:Alta', 'a')
            ->where('a.id = :altaId')
            ->setParameter('altaId', $request->get("altaId"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($alta != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $alta->getStatus()]);
            return $this->container->get("response")->success($status->getStatus(), $alta->getErrMsg());
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Crear alta.
     * @FOSRest\Post()
     */
    public function altaAction(Request $request)
    {
        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Alta.
             */
            $alta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\Alta', 'json');
            $this->container->get("app.dblogger")->success("Recibida operación ALTA OBJETO: " . $request->getContent());
            $validationErrors = $this->get('validator')->validate($alta);
            if (count($validationErrors) > 0) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }

            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $alta->setTco($em->getRepository("App:ContractKey")->findOneBy(['ckey' => $alta->getTco()]));
            $alta->setCoe($em->getRepository("App:ContractCoefficient")->findOneBy(['coefficient' => $alta->getCoe()]));
            $alta->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $alta->getCca()]));

            $alta->setDateInit();
            $alta->setProcessTime(0);
            /*
             * Paseo del tipo de identificación en caso de que sea necesario.
             */
            if ($alta->getIpt() == 6) {
                $alta->setIpf("0" . $alta->getIpf());
            }
            /*
             * La primera comprobación es básica: El alta no puede sobrepasar 60 días posteriores
             * al actual.
             * Además, la fecha no puede ser anterior a la actual.
             */
            $limitDate = (new \DateTime("now"))->modify('+60 days');
            if ($limitDate < $alta->getFra()) {
                return $this->container->get("response")->error(400, "DATE_EXPIRE_INVALID");
            }
            if ($alta->getFra()->format('Ymd') < (new \DateTime("now"))->format('Ymd')) {
                return $this->container->get("response")->error(400, "DATE_PASSED");
            }

            /*
             * Validar el tipo de empresa.
             */
            if ($alta->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }
            /*
             * Validar situación. Si no introdujo nada, el valor por defecto es 01.
             */
            if ($alta->getSit() === null) {
                $alta->setSit(01);
            }
            /*
             * Si el contrato es de tipo parcial, se requiere su coeficiente, y que éste sea válido.
             */

            $contractTimeType = $em->getRepository("App:ContractTimeType")->findOneBy(['id' => $alta->getTco()->getTimeType()]);
            if (
                $contractTimeType->getTimeType() === "TIEMPO_PARCIAL" &&
                $em->getRepository("App:ContractCoefficient")->findOneBy(['coefficient' => $alta->getCoe()]) === null
            ) {
                return $this->container->get("response")->error(400, "CONTRACT_PARTIAL_COE");
            }
            /*
             * Comprobar que no exista una solicitud similar y esté pendiente. (IPF + NAF)
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('a'))
                ->from('App:Alta', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.ipf = :ipf")
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('ipf', $alta->getIpf())
                ->setParameter('naf', $alta->getNaf())
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
                /* Agregar alta */
                $alta->setDateProcessed();
                $alta->setStatus(4);
                $em->persist($alta);
                $em->flush();

                /* Agregar alta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($alta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'ALTA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }
            $this->get("bot.manager")->logObject("Alta", $alta->getId(), $request->getContent());

            return $this->container->get("response")->success("CREATED", $alta->getId());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }
}