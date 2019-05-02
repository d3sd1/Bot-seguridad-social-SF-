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
 * @Route("/cambio/contrato")
 */
class CambioContratoController extends Controller
{
    /**
     * Eliminar solicitud de cambio de contrato consolidado de la cola.
     * @FOSRest\Delete("/consolidado/{ccId}")
     */
    public function delSolicitudCambioContratoConsolidadoAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $operation = $em->createQueryBuilder()->select(array('a'))
            ->from('App:CambioContratoConsolidado', 'a')
            ->where('a.id = :ccId')
            ->setParameter('ccId', $request->get("ccId"))
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
     * Crear petición cambio de contrato previo.
     * @FOSRest\Post("/previo")
     */
    public function cambioContratoPrevioAction(Request $request)
    {

    }

    /**
     * Consultar estado de modificación de contrato consolidado.
     * @FOSRest\Get("/consolidado/{modId}")
     */
    public function getCambioContratoConsolidadoAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $cambioTcoCons = $qb->select(array('a'))
            ->from('App:CambioContratoConsolidado', 'a')
            ->where('a.id = :modId')
            ->setParameter('modId', $request->get("modId"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($cambioTcoCons != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $cambioTcoCons->getStatus()]);
            return $this->container->get("response")->success($status->getStatus(), $cambioTcoCons->getErrMsg());
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Crear petición cambio de contrato consolidado.
     * @FOSRest\Post("/consolidado")
     */
    public function cambioContratoConsolidadoAction(Request $request)
    {
        
        try {
            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Deserializar a la entidad Cambio de Contrato Consolidado.
             */
            $cambioTcoCons = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\CambioContratoConsolidado', 'json');
            $validationErrors = $this->get('validator')->validate($cambioTcoCons);
            if (count($validationErrors) > 0 || $cambioTcoCons === null) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }
            /*
             * Rellenar los objetos para que pasen de int a obj, y no tener que poner objetos en el REST.
             */
            $cambioTcoCons->setTco($em->getRepository("App:ContractKey")->findOneBy(['ckey' => $cambioTcoCons->getTco()]));
            $cambioTcoCons->setCoe($em->getRepository("App:ContractCoefficient")->findOneBy(['coefficient' => $cambioTcoCons->getCoe()]));
            $cambioTcoCons->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $cambioTcoCons->getCca()]));

            $cambioTcoCons->setProcessTime(0);
            $cambioTcoCons->setDateInit();
            /*
             * Parseo del tipo de identificación en caso de que sea necesario.
             */
            if ($cambioTcoCons->getIpt() == 6) {
                $cambioTcoCons->setIpf("0" . $cambioTcoCons->getIpf());
            }
            /*
             * La primera comprobación es básica: El cambio de contrato consolidado no debe sobrepasar 2 días.
             * al actual.
             * Además, la fecha no puede ser anterior a dos días a la actual.
             */
            if ($cambioTcoCons->getFrc()->format('Ymd') < (new \DateTime("now"))->modify('-2 days')->format('Ymd')) {
                return $this->container->get("response")->error(400, "DATE_PASSED");
            }

            /*
             * Validar el tipo de empresa.
             */
            if ($cambioTcoCons->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Si el contrato es de tipo parcial, se requiere su coeficiente, y que éste sea válido.
             */

            $contractTimeType = $em->getRepository("App:ContractTimeType")->findOneBy(['id' => $cambioTcoCons->getTco()->getTimeType()]);

            if (
                $contractTimeType->getTimeType() === "TIEMPO_PARCIAL" &&
                $em->getRepository("App:ContractCoefficient")->findOneBy(['coefficient' => $cambioTcoCons->getCoe()]) === null
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
                ->from('App:CambioContratoConsolidado', 'a')
                ->join("App:Queue", "q", "WITH", "q.referenceId = a.id")
                ->where('a.status != :statusError')
                ->andWhere('a.status != :statusCompleted')
                ->andWhere("a.ipf = :ipf")
                ->andWhere("a.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('ipf', $cambioTcoCons->getIpf())
                ->setParameter('naf', $cambioTcoCons->getNaf())
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
                /* Agregar cambio de contrato consolidado */
                $cambioTcoCons->setDateProcessed();
                $cambioTcoCons->setStatus(4);
                $em->persist($cambioTcoCons);
                $em->flush();

                /* Agregar cambio de contrato consolidado a la cola */
                $queue = new Queue();
                $queue->setReferenceId($cambioTcoCons->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'CAMBIO_CONTRATO_CONSOLIDADO']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("CambioContratoConsolidado", $cambioTcoCons->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $cambioTcoCons->getId());
        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }
}