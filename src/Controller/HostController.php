<?php

namespace App\Controller;

use App\Entity\Host;
use App\Form\HostType;
use App\Repository\HostRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/hosts")
 */
class HostController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * JsonFieldController constructor.
     *
     * @param LoggerInterface   $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="host_index")
     * @param HostRepository $hostRepositor
     *
     * @return Response
     */
    public function index(HostRepository $hostRepositor)
    {
        $this->logger->info("View a list of hosts");
        return $this->render('host/index.html.twig', [
            'controller_name' => 'HostController',
            'itemType' => 'host',
            'pageTitle' => 'host.index',
            'pageSubTitle' => 'host.index.subtitle',
            'items' => $hostRepositor->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="host_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $host = new Host();
        $form = $this->createForm(HostType::class, $host);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($host);
            $em->flush();

            return $this->redirectToRoute('host_index');
        }

        return $this->render(
            'host/new.html.twig',
            [
                'itemType' => 'host',
                'item' => $host,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="host_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Host $host
     *
     * @return Response
     */
    public function edit(Request $request, Host $host): Response
    {
        $this->logger->info("Edit an host");
        $form = $this->createForm(HostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('host_index', ['id' => $host->getId()]);
        }

        return $this->render(
            'host/edit.html.twig',
            [
                'itemType' => 'host',
                'pageTitle' => 'host.edit',
                'pageSubTitle' => 'host.edit.subtitle',
                'item' => $host,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="host_delete", methods="DELETE")
     *
     * @param Request $request
     * @param Host $host
     *
     * @return Response
     */
    public function delete(Request $request, Host $host): Response
    {
        if ($this->isCsrfTokenValid('delete'.$host->getId(),
            $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($host);
            $em->flush();
            $this->addFlash('success', 'Host deleted');
        }

        return $this->redirectToRoute('host_index');
    }
}
