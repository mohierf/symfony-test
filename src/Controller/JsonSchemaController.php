<?php

namespace App\Controller;

use App\Entity\JsonSchema;
use App\Entity\Template;
use App\Form\JsonSchemaType;
use App\Repository\JsonSchemaRepository;
use App\Services\JsonSchemaService;
use JsonSchema\Exception\InvalidSchemaException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/schemas")
 */
class JsonSchemaController extends AbstractController
{
    /**
     * @var JsonSchemaRepository
     */
    private $jsonSchemaRepository;

    /**
     * JsonSchemaController constructor.
     *
     * @param JsonSchemaRepository $jsonSchemaRepository
     */
    public function __construct(JsonSchemaRepository $jsonSchemaRepository)
    {
        $this->jsonSchemaRepository = $jsonSchemaRepository;
    }

    /**
     * @Route("/get/{schemaName}", name="schema_serve", methods="GET")
     *
     * @param $schemaName
     *
     * @return JsonResponse
     */
    public function serve($schemaName): JsonResponse
    {
        if ('prod' === $this->container->getParameter('kernel.environment')) {
            throw new AccessDeniedHttpException();
        }

        /** @var JsonSchema $jsonSchema */
        $jsonSchema = $this->jsonSchemaRepository->findOneBy(['name' => $schemaName]);
        if (!$jsonSchema) {
            throw $this->createNotFoundException(sprintf('Schema not found : %s', $schemaName));
        }

        return new JsonResponse($jsonSchema->getContent(), 200, [], true);
    }

    /**
     * @Route("/{id}", name="json_schema_delete", methods="DELETE")
     *
     * @param Request    $request
     * @param JsonSchema $jsonSchema
     *
     * @return Response
     */
    public function delete(Request $request, JsonSchema $jsonSchema): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jsonSchema->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($jsonSchema);
            $em->flush();
        }

        return $this->redirectToRoute('json_schema_index');
    }

    /**
     * @Route("/{id}/edit", name="json_schema_edit", methods="GET|POST")
     *
     * @param Request    $request
     * @param JsonSchema $jsonSchema
     *
     * @return Response
     */
    public function edit(Request $request, JsonSchema $jsonSchema, JsonSchemaService $schemaService, LoggerInterface $logger): Response
    {
        // this returns a single item
        $metaSchema = $this->jsonSchemaRepository->find(1);

        $form = $this->createForm(JsonSchemaType::class, $jsonSchema);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('json_schema_index', ['id' => $jsonSchema->getId()]);
        }

        /* Validate the Json according to the Json meta schema */
        try {
            $schemaService->validate($jsonSchema->getContent(), $metaSchema);
        } catch (InvalidSchemaException $e) {
            return new JsonResponse(['message' => sprintf('Invalid schema. JSON does not validate due to violations : %s', $e->getMessage())], JsonResponse::HTTP_BAD_REQUEST);
        }

        $json_list = $schemaService->getFieldsFromSchema($jsonSchema);
//        $json_list = $schemaService->getPlaceholdersFromSchema($jsonSchema);


        return $this->render(
            'json_schema/edit.html.twig',
            [
                'meta_schema_name' => $metaSchema->getName(),
                'meta_schema' => $metaSchema->getContent(),
                'json_schema' => $jsonSchema,
                'json_schema_list' => $json_list,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/", name="json_schema_index", methods="GET")
     *
     * @param JsonSchemaRepository $jsonSchemaRepository
     *
     * @return Response
     */
    public function index(JsonSchemaRepository $jsonSchemaRepository): Response
    {
        return $this->render('json_schema/index.html.twig', ['json_schemas' => $jsonSchemaRepository->findAll()]);
    }

    /**
     * @Route("/new", name="json_schema_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function new(Request $request): Response
    {
        $jsonSchema = new JsonSchema();
        $form = $this->createForm(JsonSchemaType::class, $jsonSchema);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($jsonSchema);
            $em->flush();

            return $this->redirectToRoute('json_schema_index');
        }

        return $this->render(
            'json_schema/new.html.twig',
            [
                'json_schema' => $jsonSchema,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="json_schema_show", methods="GET")
     *
     * @param JsonSchema $jsonSchema
     *
     * @return Response
     */
    public function show(JsonSchema $jsonSchema): Response
    {
        return $this->render('json_schema/show.html.twig', ['json_schema' => $jsonSchema]);
    }
}
