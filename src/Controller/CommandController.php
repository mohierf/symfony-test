<?php

namespace App\Controller;

use App\Entity\Command;
use App\Form\CommandType;
use App\Repository\CommandRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/commands")
 */
class CommandController extends AbstractController
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
     * @Route("/", name="command_index")
     * @param CommandRepository $commandRepository
     * @return Response
     */
    public function index(CommandRepository $commandRepository)
    {
        $this->logger->info("View a list of commands");
        return $this->render('command/index.html.twig', [
            'controller_name' => 'CommandController',
            'itemType' => 'command',
            'pageTitle' => 'command.index',
            'pageSubTitle' => 'command.index.subtitle',
            'items' => $commandRepository->findAll()
        ]);
    }

    /**
     * @Route("/new", name="command_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $command = new Command();
        $form = $this->createForm(CommandType::class, $command);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($command);
            $em->flush();

            return $this->redirectToRoute('command_index');
        }

        return $this->render(
            'command/new.html.twig',
            [
                'itemType' => 'command',
                'item' => $command,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="command_show", methods="GET")
     * @Route("/{id}/show", name="command_show", methods="GET")
     *
     * @param Command $command
     *
     * @return Response
     */
    public function show(Command $command): Response
    {
        return $this->render('command/show.html.twig', ['command' => $command]);
    }

    /**
     * @Route("/{id}/edit", name="command_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Command $command
     *
     * @return Response
     */
    public function edit(Request $request, Command $command): Response
    {
        $this->logger->info("Edit a command");
        $form = $this->createForm(CommandType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('command_index', ['id' => $command->getId()]);
        }

        return $this->render(
            'command/edit.html.twig',
            [
                'itemType' => 'command',
                'pageTitle' => 'command.edit',
                'pageSubTitle' => 'command.edit.subtitle',
                'item' => $command,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="command_delete", methods="DELETE")
     *
     * @param Request $request
     * @param Command $command
     *
     * @return Response
     */
    public function delete(Request $request, Command $command): Response
    {
        if ($this->isCsrfTokenValid('delete'.$command->getId(),
            $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($command);
            $em->flush();
            $this->addFlash('success', 'Command deleted');
        }

        return $this->redirectToRoute('command_index');
    }
}
