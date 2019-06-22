<?php

namespace App\Controller;

use App\Entity\Realm;
use App\Form\RealmType;
use App\Repository\RealmRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/realms")
 */
class RealmController extends AbstractController
{
    /**
     * @Route("/", name="realm_index")
     * @param RealmRepository $realmRepository
     * @param LoggerInterface $logger
     *
     * @param SerializerInterface $serializer
     *
     * @return Response
     */
    public function index(RealmRepository $realmRepository,
                          LoggerInterface $logger,
                          SerializerInterface $serializer)
    {
        $realms = $realmRepository->findAll();
        $jsonContent = $serializer->serialize($realms, 'json');
        $logger->info("Json realms: " . json_encode($jsonContent));

        return $this->render('realm/index.html.twig', [
            'controller_name' => 'RealmController',
            'items' => $realmRepository->findAll(),
            'json_content' => $jsonContent,
        ]);
    }

    /**
     * @Route("/new", name="realm_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $realm = new Realm();
        $form = $this->createForm(RealmType::class, $realm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($realm);
            $em->flush();

            return $this->redirectToRoute('realm_index');
        }

        return $this->render(
            'realm/new.html.twig',
            [
                'realm' => $realm,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="realm_show", methods="GET")
     * @Route("/{id}/show", name="realm_show", methods="GET")
     *
     * @param Realm $realm
     *
     * @return Response
     */
    public function show(Realm $realm): Response
    {
        return $this->render('realm/show.html.twig', ['realm' => $realm]);
    }

    /**
     * @Route("/{id}/edit", name="realm_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param Realm $realm
     * @param LoggerInterface $logger
     *
     * @return Response
     */
    public function edit(Request $request, Realm $realm, LoggerInterface $logger): Response
    {
        $logger->info("Edit a realm");
        $form = $this->createForm(RealmType::class, $realm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('realm_index', ['id' => $realm->getId()]);
        }

        return $this->render(
            'realm/edit.html.twig',
            [
                'realm' => $realm,
                'form' => $form->createView(),
            ]
        );
    }

}
