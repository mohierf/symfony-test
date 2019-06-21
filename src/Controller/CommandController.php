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
     * @Route("/", name="command_index")
     * @param CommandRepository $commandRepository
     * @return Response
     */
    public function index(CommandRepository $commandRepository, LoggerInterface $logger)
    {
        $logger->info("View a list of commands");
        return $this->render('command/index.html.twig', [
            'controller_name' => 'CommandController',
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
                'command' => $command,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="command_show", methods="GET")
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
     * @Route("/edit", name="command_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Command $command
     * @param LoggerInterface $logger
     *
     * @return Response
     */
    public function edit(Request $request, Command $command, LoggerInterface $logger): Response
    {
        $logger->info("Edit a command");
        $form = $this->createForm(CommandType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('command_index', ['id' => $command->getId()]);
        }

        return $this->render(
            'command/edit.html.twig',
            [
                'command' => $command,
                'form' => $form->createView(),
            ]
        );
    }

}
