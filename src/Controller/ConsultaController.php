<?php

namespace App\Controller;

use App\Entity\Queue;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as FOSRest;

/**
 * Brand controller.
 *
 * @Route("/consulta")
 */
class ConsultaController extends Controller
{
    /**
     * Consultar el estado de una petición previa.
     * @FOSRest\Get("/naf/{id}")
     */
    public function getEstadoNafAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $consulta = $qb->select(array('a'))
            ->from('App:ConsultaNaf', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $request->get("id"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($consulta != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $consulta->getStatus()]);

            $data = "";
            if ($status->getStatus() === "COMPLETED") {
                $data = $consulta->getData();
            } else if ($status->getStatus() === "ERROR") {
                $data = $consulta->getErrMsg();
            }
            return $this->container->get("response")->success($status->getStatus(), $data);
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Consultar NAF por IPF.
     * @FOSRest\Post("/naf")
     */
    public function consultaNafAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        try {
            /*
             * Deserializar a la entidad Consulta NAF.
             */
            $consulta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\ConsultaNaf', 'json');
            $validationErrors = $this->get('validator')->validate($consulta);
            if (count($validationErrors) > 0) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }
            /*
             * Comprobar que no exista una solicitud similar y esté pendiente.
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('c'))
                ->from('App:ConsultaNaf', 'c')
                ->join("App:Queue", "q", "WITH", "q.referenceId = c.id")
                ->where('c.status != :statusError')
                ->andWhere('c.status != :statusCompleted')
                ->andWhere("c.ipf = :ipf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('ipf', $consulta->getIpf())
                ->orderBy('c.dateProcessed', 'DESC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
            if ($task != null) {
                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

                /* Devolver resultado */
                return $this->container->get("response")->success("RETRIEVED", $task->getId());
            } else {
                /* Agregar consulta */
                $consulta->setDateProcessed();
                $consulta->setStatus(4);
                $consulta->setDateInit();
                $consulta->setProcessTime(0);
                $em->persist($consulta);
                $em->flush();

                /* Agregar consulta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($consulta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'CONSULTA_NAF']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("ConsultaNaf", $consulta->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $consulta->getId());

        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }

    /**
     * Consultar el estado de una petición previa.
     * @FOSRest\Get("/ipf/{id}")
     */
    public function getEstadoIpfAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $consulta = $qb->select(array('a'))
            ->from('App:ConsultaIpf', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $request->get("id"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($consulta != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $consulta->getStatus()]);

            $data = "";
            if ($status->getStatus() === "COMPLETED") {
                $data = $consulta->getData();
            } else if ($status->getStatus() === "ERROR") {
                $data = $consulta->getErrMsg();
            }
            return $this->container->get("response")->success($status->getStatus(), $data);
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Consultar IPF por NAF.
     * @FOSRest\Post("/ipf")
     */
    public function consultaIpfAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        try {
            /*
             * Deserializar a la entidad Consulta IPF.
             */
            $consulta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\ConsultaIpf', 'json');
            $validationErrors = $this->get('validator')->validate($consulta);
            if (count($validationErrors) > 0) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }
            /*
             * Comprobar que no exista una solicitud similar y esté pendiente.
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('c'))
                ->from('App:ConsultaIpf', 'c')
                ->join("App:Queue", "q", "WITH", "q.referenceId = c.id")
                ->where('c.status != :statusError')
                ->andWhere('c.status != :statusCompleted')
                ->andWhere("c.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('naf', $consulta->getNaf())
                ->orderBy('c.dateProcessed', 'DESC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($task != null) {
                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

                /* Devolver resultado */
                return $this->container->get("response")->success("RETRIEVED", $task->getId());
            } else {
                /* Agregar consulta */
                $consulta->setDateProcessed();
                $consulta->setStatus(4);
                $consulta->setProcessTime(0);
                $em->persist($consulta);
                $em->flush();

                /* Agregar consulta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($consulta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'CONSULTA_IPF']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("ConsultaIpf", $consulta->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $consulta->getId());

        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }


    /**
     * Consultar el estado de una petición de todas las altas de un CCC.
     * @FOSRest\Get("/altas/{id}")
     */
    public function getEstadoAltasCCCAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $consulta = $qb->select(array('a'))
            ->from('App:ConsultaAltasCcc', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $request->get("id"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($consulta != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $consulta->getStatus()]);

            $data = "";
            if ($status->getStatus() === "COMPLETED") {
                $data = $consulta->getData();
            } else if ($status->getStatus() === "ERROR") {
                $data = $consulta->getErrMsg();
            }
            $fileContents = "";
            if (file_exists($data)) {
                $fileContents = file_get_contents($data);
            }
            return $this->container->get("response")->success($status->getStatus(), $fileContents);
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Consultar los altas actuales de la empresa.
     * @FOSRest\Post("/altas")
     */
    public function consultaAltasAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        try {
            /*
             * Deserializar a la entidad Consulta NAF.
             */
            $consulta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\ConsultaAltasCcc', 'json');
            $validationErrors = $this->get('validator')->validate($consulta);
            if (count($validationErrors) > 0) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }
            $consulta->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $consulta->getCca()]));

            /*
             * Validar el tipo de empresa.
             */
            if ($consulta->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Comprobar que no exista una solicitud similar y esté pendiente.
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('c'))
                ->from('App:ConsultaAltasCcc', 'c')
                ->join("App:Queue", "q", "WITH", "q.referenceId = c.id")
                ->where('c.status != :statusError')
                ->andWhere('c.status != :statusCompleted')
                ->andWhere("c.cca = :cca")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('cca', $consulta->getCca())
                ->orderBy('c.dateProcessed', 'DESC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($task != null) {
                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

                /* Devolver resultado */
                return $this->container->get("response")->success("RETRIEVED", $task->getId());
            } else {
                /* Agregar consulta */
                $consulta->setDateProcessed();
                $consulta->setStatus(4);
                $consulta->setProcessTime(0);
                $em->persist($consulta);
                $em->flush();

                /* Agregar consulta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($consulta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'CONSULTA_ALTAS_CCC']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("ConsultaAltasCCC", $consulta->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $consulta->getId());

        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }

    /**
     * Consultar el estado de una petición de TA.
     * @FOSRest\Get("/ta/{id}")
     */
    public function getEstadoTaAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $consulta = $qb->select(array('a'))
            ->from('App:ConsultaTa', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $request->get("id"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($consulta != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $consulta->getStatus()]);

            $data = "";
            if ($status->getStatus() === "COMPLETED") {
                $data = base64_encode(file_get_contents($consulta->getData()));
            } else if ($status->getStatus() === "ERROR") {
                $data = $consulta->getErrMsg();
            }
            return $this->container->get("response")->success($status->getStatus(), $data);
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Descargar TA.
     * @FOSRest\Post("/ta")
     */
    public function consultaTaAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        try {
            /*
             * Deserializar a la entidad Consulta NAF.
             */
            $consulta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\ConsultaTa', 'json');
            $validationErrors = $this->get('validator')->validate($consulta);
            if (count($validationErrors) > 0) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }
            $consulta->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $consulta->getCca()]));

            /*
             * Validar el tipo de empresa.
             */
            if ($consulta->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Validar la fecha.
             */
            if ($consulta->getFrc() === null) {
                return $this->container->get("response")->error(400, "INVALID_DATE");
            }

            /*
             * Comprobar que no exista una solicitud similar y esté pendiente.
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('c'))
                ->from('App:ConsultaTa', 'c')
                ->join("App:Queue", "q", "WITH", "q.referenceId = c.id")
                ->where('c.status != :statusError')
                ->andWhere('c.status != :statusCompleted')
                ->andWhere("c.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('naf', $consulta->getNaf())
                ->orderBy('c.dateProcessed', 'DESC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($task != null) {
                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

                /* Devolver resultado */
                return $this->container->get("response")->success("RETRIEVED", $task->getId());
            } else {
                /* Agregar consulta */
                $consulta->setDateProcessed();
                $consulta->setStatus(4);
                $consulta->setDateInit();
                $consulta->setProcessTime(0);
                $em->persist($consulta);
                $em->flush();

                /* Agregar consulta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($consulta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'CONSULTA_TA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("ConsultaTA", $consulta->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $consulta->getId());

        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }




    /**
     * Consultar el estado de una petición de consulta de Alta.
     * @FOSRest\Get("/alta/{id}")
     */
    public function getEstadoAltaConsultaAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        $qb = $em->createQueryBuilder();
        $consulta = $qb->select(array('a'))
            ->from('App:ConsultaAlta', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $request->get("id"))
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($consulta != null) {
            /* Enviar notificación al bot para procesar cola */
            //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

            $status = $em->getRepository("App:ProcessStatus")->findOneBy(['id' => $consulta->getStatus()]);

            $data = "";
            if ($status->getStatus() === "COMPLETED") {
                $data = $consulta->getData();
            } else if ($status->getStatus() === "ERROR") {
                $data = $consulta->getErrMsg();
            }
            return $this->container->get("response")->success($status->getStatus(), $data);
        } else {
            return $this->container->get("response")->error(400, "NOT_FOUND");
        }
    }

    /**
     * Consultar estado Alta.
     * @FOSRest\Post("/alta")
     */
    public function consultaAltaAction(Request $request)
    {
        
        $em = $this->get("doctrine.orm.entity_manager");
        try {
            /*
             * Deserializar a la entidad Consulta NAF.
             */
            $consulta = $this->get("jms_serializer")->deserialize($request->getContent(), 'App\Entity\ConsultaAlta', 'json');
            $validationErrors = $this->get('validator')->validate($consulta);
            if (count($validationErrors) > 0) {
                throw new \JMS\Serializer\Exception\RuntimeException("Could not deserialize entity: " . $validationErrors);
            }
            $consulta->setCca($em->getRepository("App:ContractAccounts")->findOneBy(['name' => $consulta->getCca()]));

            /*
             * Validar el tipo de empresa.
             */
            if ($consulta->getCca() === null) {
                return $this->container->get("response")->error(400, "CONTRACT_ACCOUNT_NOT_FOUND");
            }

            /*
             * Validar la fecha.
             */
            if ($consulta->getFrc() === null) {
                return $this->container->get("response")->error(400, "INVALID_DATE");
            }
            /*
             * Comprobar que no exista una solicitud similar y esté pendiente.
             * Si no hay ninguna, se crea una nueva y se agrega a la cola para el bot.
             * Si existe una previa, se devuelve la ID de la previa, excepto:
             * Si existe y esta en estado de error o completada, que se genera una nueva.
             */

            $qb = $em->createQueryBuilder();
            $task = $qb->select(array('c'))
                ->from('App:ConsultaAlta', 'c')
                ->join("App:Queue", "q", "WITH", "q.referenceId = c.id")
                ->where('c.status != :statusError')
                ->andWhere('c.status != :statusCompleted')
                ->andWhere("c.naf = :naf")
                ->setParameter('statusError', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'ERROR']))
                ->setParameter('statusCompleted', $em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'COMPLETED']))
                ->setParameter('naf', $consulta->getNaf())
                ->orderBy('c.dateProcessed', 'DESC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($task != null) {
                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();

                /* Devolver resultado */
                return $this->container->get("response")->success("RETRIEVED", $task->getId());
            } else {


                /* Agregar consulta */
                $consulta->setDateProcessed();
                $consulta->setStatus(4);
                $consulta->setDateInit();
                $consulta->setProcessTime(0);
                $em->persist($consulta);
                $em->flush();

                /* Agregar consulta a la cola */
                $queue = new Queue();
                $queue->setReferenceId($consulta->getId());
                $queue->setDateAdded();
                $queue->setProcessType($em->getRepository("App:ProcessType")->findOneBy(['type' => 'CONSULTA_ALTA']));
                $em->persist($queue);
                $em->flush();

                /* Enviar notificación al bot para procesar cola */
                //DEPRECEATED $REAL TIME SOCKETS DUE TO PHP BAD SOCKETS $this->get("app.sockets")->notify();
            }

            $this->get("bot.manager")->logObject("ConsultaEstadoAlta", $consulta->getId(), $request->getContent());
            return $this->container->get("response")->success("CREATED", $consulta->getId());

        } catch (\Exception $e) {
            return $this->container->get("response")->error(400, $this->get("app.exception")->capture($e), $e->getMessage());
        }
    }
}