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
 * @Route("/baja/anulacion")
 */
class AnulacionBajaController extends Controller
{

    /**
     * Eliminar solicitud baja previa de la cola.
     * @FOSRest\Delete("/previa/{bajaId}")
     */
    public function delSolicitudBajaPreviaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:AnulacionBajaPrevia', 'a')
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
     * Consultar estado de baja previa.
     * @FOSRest\Get("/previa/{bajaId}")
     */
    public function getAnulacionBajaPreviaAction(Request $request)
    {

        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $alta = $qb->select(array('a'))
            ->from('App:AnulacionBajaPrevia', 'a')
            ->where('a.id = :bajaId')
            ->setParameter('bajaId', $request->get("bajaId"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($alta != null) {
            /* Enviar notificación al bot para procesar cola */

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $alta->getStatus()]);
            return $this->container->get("response")->success($status->getStatus(), $alta->getErrMsg());
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Crear solicitud de anulación de baja previa.
     * @FOSRest\Post("/previa")
     */
    public function anulacionBajaPreviaAction(Request $request)
    {

        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Baja.
             */
            $anulacionBaja = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\AnulacionBajaPrevia', 'json');
            $validationErrors = $this->get('validator')->validate($anulacionBaja);
            if (count($validationErrors) > 0) {
                return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
            }

            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $anulacionBaja->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $anulacionBaja->getCca()]));

            $anulacionBaja->setDateInit();
            $anulacionBaja->setProcessTime(0);
            /*
             * La primera comprobación es básica: La petición de baja previa no puede sobrepasar 60 días posteriores
             * al actual.
             * Además, la fecha no puede ser anterior a la actual.
             */
            $limitDate = (new \DateTime("now"))->modify('+60 days');
            if ($limitDate < $anulacionBaja->getFrb()) {
                return $this->container->get("response")->error(400, "DATE_EXPIRE_INVALID");
            }
            if ($anulacionBaja->getFrb() > (new \DateTime("now"))->modify('+3 days')) {
                return $this->container->get("response")->error(400, "DATE_PASSED");
            }

            /*
             * Validar el tipo de empresa.
             */
            if ($anulacionBaja->getCca() === null) {
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
                ->from('App:AnulacionBajaPrevia', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('naf', $anulacionBaja->getNaf())
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
                /* Agregar anulación de baja */
                $anulacionBaja->setDateProcessed();
                $anulacionBaja->setStatus(4);
                $em->persist($anulacionBaja);
                $em->flush();

                /* Agregar baja a la cola */
                $queue = new Queue();
                $queue->setReferenceId($anulacionBaja->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'ANULACION_BAJA_PREVIA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }
            $this->get("bot.manager")->logObject("AnulacionBajaPrevia", $anulacionBaja->getId(), $request->getContent());

            return $this->container->get("response")->success("CREATED", $anulacionBaja->getId());
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, "UNCAUGHT_EXCEPTION");
        }
    }


    /**
     * Eliminar solicitud baja consolidada de la cola.
     * @FOSRest\Delete("/consolidada/{bajaId}")
     */
    public function delSolicitudBajaConsolidadaAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:AnulacionBajaConsolidada', 'a')
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
     * Consultar estado de baja consolidada.
     * @FOSRest\Get("/consolidada/{bajaId}")
     */
    public function getAnulacionBajaConsolidadaAction(Request $request)
    {

        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $alta = $qb->select(array('a'))
            ->from('App:AnulacionBajaConsolidada', 'a')
            ->where('a.id = :bajaId')
            ->setParameter('bajaId', $request->get("bajaId"))
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
     * Crear solicitud de anulación de baja consolidada.
     * @FOSRest\Post("/consolidada")
     */
    public function anulacioBajaConsolidadaAction(Request $request)
    {

        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Baja.
             */
            $anulacionBaja = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\AnulacionBajaConsolidada', 'json');
            $validationErrors = $this->get('validator')->validate($anulacionBaja);
            if (count($validationErrors) > 0) {
                return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
            }

            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $anulacionBaja->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $anulacionBaja->getCca()]));

            $anulacionBaja->setDateInit();
            $anulacionBaja->setProcessTime(0);
            /*
             * La primera comprobación es básica: La petición de baja consolidada no puede ser anterior a la actual.
             */
            if ($anulacionBaja->getFrb() > (new \DateTime("now"))->modify('+3 days')) {
                return $this->container->get("response")->error(400, "DATE_PASSED");
            }

            /*
             * Validar el tipo de empresa.
             */
            if ($anulacionBaja->getCca() === null) {
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
                ->from('App:AnulacionBajaConsolidada', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.ipf = :ipf")
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('ipf', $anulacionBaja->getIpf())
                ->setParameter('naf', $anulacionBaja->getNaf())
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
                /* Agregar anulación de baja */
                $anulacionBaja->setDateProcessed();
                $anulacionBaja->setStatus(4);
                $em->persist($anulacionBaja);
                $em->flush();

                /* Agregar baja a la cola */
                $queue = new Queue();
                $queue->setReferenceId($anulacionBaja->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'ANULACION_BAJA_CONSOLIDADA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }
            $this->get("bot.manager")->logObject("AnulacionBajaConsolidada", $anulacionBaja->getId(), $request->getContent());

            return $this->container->get("response")->success("CREATED", $anulacionBaja->getId());
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->container->get("response")->error(400, "INVALID_OBJECT", $e->getMessage());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, "UNCAUGHT_EXCEPTION");
        }
    }
}