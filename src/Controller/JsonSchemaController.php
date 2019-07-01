<?php

namespace App\Controller;

use App\Entity\JsonSchema;
use App\Form\JsonSchemaType;
use App\Repository\JsonSchemaRepository;
use App\Services\JsonSchemaService;
use JsonSchema\Exception\InvalidSchemaException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    const META_SCHEMA_NAME = 'MetaSchema';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JsonSchemaRepository
     */
    private $jsonSchemaRepository;

    /**
     * @var JsonSchemaService
     */
    private $jsonSchemaService;

    /**
     * JsonSchemaController constructor.
     *
     * @param LoggerInterface      $logger
     * @param JsonSchemaRepository $jsonSchemaRepository
     * @param JsonSchemaService    $jsonSchemaService
     */
    public function __construct(LoggerInterface $logger,
                                JsonSchemaRepository $jsonSchemaRepository,
                                JsonSchemaService $jsonSchemaService)
    {
        $this->logger = $logger;
        $this->jsonSchemaRepository = $jsonSchemaRepository;
        $this->jsonSchemaService = $jsonSchemaService;
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
            $this->addFlash('success', 'Schema deleted');
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
    public function edit(Request $request, JsonSchema $jsonSchema): Response
    {
        if (self::META_SCHEMA_NAME == $jsonSchema->getName()) {
            $this->addFlash('warning', 'Meta schema is not editable.');

            return $this->redirectToRoute('json_schema_index', ['id' => $jsonSchema->getId()]);
        }

        $form = $this->createForm(JsonSchemaType::class, $jsonSchema);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var JsonSchema $metaSchema */
                $metaSchema = $this->jsonSchemaRepository->findOneBy(['name' => self::META_SCHEMA_NAME]);

                // Validate the Json according to the Json meta sc
                $this->jsonSchemaService->validate($jsonSchema->getContent(), $metaSchema, true);
                $this->addFlash('success', 'Schema is a valid Json schema.');

                // Update the related Json schema
                $this->jsonSchemaService->getJsonFromFields($jsonSchema);

                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Schema updated.');
            } catch (InvalidSchemaException $e) {
                $this->addFlash('danger', sprintf('Invalid schema, violations: %s', $e->getMessage()));
            }

            return $this->redirectToRoute('json_schema_index', ['id' => $jsonSchema->getId()]);
        }

        // Build and get the Json fields from a schema
        $this->jsonSchemaService->getFieldsFromSchema($jsonSchema);

        return $this->render(
            'json_schema/edit.html.twig',
            [
                'json_schema' => $jsonSchema,
                'json_text' => $jsonSchema->getContent(),
                'items' => $jsonSchema->getJsonFields(),
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/validate", name="json_schema_validate", methods="GET")
     *
     * A simple redirect to the edition route will provoke a Schema validation.
     *
     * @param JsonSchema $jsonSchema
     *
     * @return Response
     */
    public function validate(JsonSchema $jsonSchema): Response
    {
        return $this->redirectToRoute('json_schema_edit', ['id' => $jsonSchema->getId()]);
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
            try {
                /** @var JsonSchema $metaSchema */
                $metaSchema = $this->jsonSchemaRepository->findOneBy(['name' => self::META_SCHEMA_NAME]);
                $this->jsonSchemaService->validate($jsonSchema->getContent(), $metaSchema, true);
                $this->addFlash('success', 'Schema is a valid Json schema.');

                // Build and get the Json fields from a schema
                $this->jsonSchemaService->getFieldsFromSchema($jsonSchema);

                $em = $this->getDoctrine()->getManager();
                $em->persist($jsonSchema);
                $em->flush();

                return $this->redirectToRoute('json_schema_index');
            } catch (InvalidSchemaException $e) {
                $this->addFlash('danger', sprintf('Invalid schema, violations: %s', $e->getMessage()));
            }
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
