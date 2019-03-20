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
 * @Route("/alta/anulacion")
 */
class AnulacionAltaController extends Controller
{

    /**
     * Eliminar alta previa de la cola.
     * @FOSRest\Delete("/previa/{altaId}")
     */
    public function deleteAnulacionAltaPreviaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:AnulacionAltaPrevia', 'a')
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
     * Consultar estado de alta previa.
     * @FOSRest\Get("/previa/{altaId}")
     */
    public function getAnulacionAltaPreviaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $alta = $qb->select(array('a'))
            ->from('App:AnulacionAltaPrevia', 'a')
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
     * Crear solicitud de anulación de alta previa.
     * @FOSRest\Post("/previa")
     */
    public function anulacionAltaPreviaAction(Request $request)
    {


        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Anulación Alta.
             */
            $anulacionAlta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\AnulacionAltaPrevia', 'json');
            $validationErrors = $this->get('validator')->validate($anulacionAlta);
            if (count($validationErrors) > 0) {
                return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
            }

            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $anulacionAlta->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $anulacionAlta->getCca()]));

            $anulacionAlta->setDateInit();
            $anulacionAlta->setProcessTime(0);
            /*
             * La primera comprobación es básica: La petición de alta previa no puede sobrepasar 60 días posteriores
             * al actual.
             * Además, la fecha no puede ser anterior a la actual.
             */
            $limitDate = (new \DateTime("now"))->modify('+60 days');
            if ($limitDate < $anulacionAlta->getFra()) {
                return $this->container->get("response")->error(400, "DATE_EXPIRE_INVALID");
            }
            if ($anulacionAlta->getFra() > (new \DateTime("now"))->modify('+3 days')) {
                return $this->container->get("response")->error(400, "DATE_PASSED");
            }

            /*
             * Validar el tipo de empresa.
             */
            if ($anulacionAlta->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Comprobar que no exista una solicitud similar y esté pendiente. (IPF + NAF)
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */
            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('a'))
                ->from('App:AnulacionAltaPrevia', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('naf', $anulacionAlta->getNaf())
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
                /* Agregar anulación de alta */
                $anulacionAlta->setDateProcessed();
                $anulacionAlta->setStatus(4);
                $em->persist($anulacionAlta);
                $em->flush();

                /* Agregar alta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($anulacionAlta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'ANULACION_ALTA_PREVIA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("AnulacionAltaPrevia", $anulacionAlta->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $anulacionAlta->getId());
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, "UNCAUGHT_EXCEPTION");
        }
    }

    /**
     * Eliminar alta consolidada de la cola.
     * @FOSRest\Delete("/consolidada/{altaId}")
     */
    public function deleteAnulacionAltaConsolidadaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:AnulacionAltaConsolidada', 'a')
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
     * Consultar estado de alta consolidada.
     * @FOSRest\Get("/consolidada/{altaId}")
     */
    public function getAnulacionAltaConsolidadaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $alta = $qb->select(array('a'))
            ->from('App:AnulacionAltaConsolidada', 'a')
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
     * @FOSRest\Post("/consolidada")
     */
    public function anulacionAltaConsolidadaAction(Request $request)
    {

        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Anulación Alta.
             */
            $anulacionAlta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\AnulacionAltaConsolidada', 'json');
            $validationErrors = $this->get('validator')->validate($anulacionAlta);
            if (count($validationErrors) > 0) {
                return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
            }

            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $anulacionAlta->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $anulacionAlta->getCca()]));

            $anulacionAlta->setDateInit();
            $anulacionAlta->setProcessTime(0);
            /*
             * Validar el tipo de empresa.
             */
            if ($anulacionAlta->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Comprobar que no exista una solicitud similar y esté pendiente. (IPF + NAF)
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */
            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('a'))
                ->from('App:AnulacionAltaConsolidada', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('naf', $anulacionAlta->getNaf())
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
                /* Agregar anulación de alta */
                $anulacionAlta->setDateProcessed();
                $anulacionAlta->setStatus(4);
                $em->persist($anulacionAlta);
                $em->flush();

                /* Agregar alta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($anulacionAlta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'ANULACION_ALTA_CONSOLIDADA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }
            $this->get("bot.manager")->logObject("AnulacionAltaConsolidada", $anulacionAlta->getId(), $request->getContent());

            return $this->container->get("response")->success("CREATED", $anulacionAlta->getId());
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, "UNCAUGHT_EXCEPTION");
        }
    }
}