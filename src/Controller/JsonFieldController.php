<?php

namespace App\Controller;

use App\Entity\JsonField;
use App\Entity\JsonSchema;
use App\Form\JsonFieldType;
use App\Repository\JsonFieldRepository;
use App\Services\JsonSchemaService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/json_fields")
 */
class JsonFieldController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JsonSchemaService
     */
    private $jsonSchemaService;

    /**
     * JsonFieldController constructor.
     *
     * @param LoggerInterface   $logger
     * @param JsonSchemaService $jsonSchemaService
     */
    public function __construct(LoggerInterface $logger, JsonSchemaService $jsonSchemaService)
    {
        $this->logger = $logger;
        $this->jsonSchemaService = $jsonSchemaService;
    }

    /**
     * @Route("/", name="json_field_index")
     *
     * @param Request             $request
     * @param JsonFieldRepository $jsonFieldRepository
     *
     * @return Response
     */
    public function index(Request $request, JsonFieldRepository $jsonFieldRepository)
    {
        $jsonSchemaRepository = $this->getDoctrine()->getRepository(JsonSchema::class);

        $required_schema_name = $request->query->get('schema');
        $this->logger->info('Required schema: '.$required_schema_name);

        $jsonSchemas = [];
        $jsonFields = [];
        if (empty($required_schema_name)) {
            $jsonSchemas = $jsonSchemaRepository->findAll();
            $jsonFields = $jsonFieldRepository->findAll();
            $this->logger->info('Viewing Json fields for all schemas');
        } else {
            // Try to get with the name
            $schema = $jsonSchemaRepository->findOneBy(['name' => $required_schema_name]);
            if ($schema) {
                $this->logger->info('Schema: '.$schema->getName());
                $jsonSchemas[] = $schema;
                $jsonFields = $schema->getJsonFields();
            } else {
                // Try with an id
                $schema = $jsonSchemaRepository->find($required_schema_name);
                $this->logger->info('-> schema: '.serialize($schema));
                if ($schema) {
                    $this->logger->info('Schema found by id: '.$schema->getName());
                    $jsonSchemas[] = $schema;
                    $jsonFields = $schema->getJsonFields();
                    $required_schema_name = $schema->getName();
                }
            }
            $this->logger->info("Viewing Json fields for the schema: $required_schema_name");
        }

        return $this->render('json_field/index.html.twig', [
            'itemType' => 'json_field',
            'controller_name' => 'JsonFieldController',
            'json_schema' => $required_schema_name,
            'json_schemas' => $jsonSchemas,
            'items' => $jsonFields,
        ]);
    }

    /**
     * Request to create a new Json field. Some query parameters:
     * - schema: schema of the new field
     * - parent: parent of the new field.
     *
     * @Route("/new", name="json_field_new", methods="GET|POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function new(Request $request): Response
    {
        $required_schema = $request->query->get('schema');
        $this->logger->info('Required schema: '.$required_schema);

        $required_parent = $request->query->get('parent');
        $this->logger->info('Required parent: '.$required_parent);

        $jsonField = new JsonField();
        $form = $this->createForm(JsonFieldType::class, $jsonField);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $em->persist($jsonField);
                $em->flush();
                $this->addFlash('success', 'New field created');

                // Update the related Json schema
                $this->jsonSchemaService->getJsonFromFields($jsonField->getJsonSchema());

                if (!empty($required_schema)) {
                    return $this->redirectToRoute('json_schema_edit', ['id' => $required_schema]);
                }

                return $this->redirectToRoute('json_field_index');
            } catch (UniqueConstraintViolationException $exp) {
                $this->addFlash(
                    'danger',
                    'A field still exists with that name in the required schema'
                );
            }
        }

        return $this->render(
            'json_field/new.html.twig',
            [
                'schema_id' => $required_schema,
                'parent_id' => $required_parent,
                'json_field' => $jsonField,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="json_field_delete", methods="DELETE")
     *
     * @param Request   $request
     * @param JsonField $jsonField
     *
     * @return Response
     */
    public function delete(Request $request, JsonField $jsonField): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jsonField->getId(),
            $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($jsonField);
            $em->flush();
            $this->addFlash('success', 'Field (and descendants deleted');
        }

        return $this->redirectToRoute('json_field_index');
    }

    /**
     * @Route("/{id}", name="json_field_show", methods="GET")
     * @Route("/{id}/show", name="json_field_show", methods="GET")
     *
     * @param JsonField $jsonField
     *
     * @return Response
     */
    public function show(JsonField $jsonField): Response
    {
        return $this->render('json_field/show.html.twig', ['item' => $jsonField]);
    }

    /**
     * @Route("/{id}/edit", name="json_field_edit", methods="GET|POST")
     *
     * @param Request   $request
     * @param JsonField $jsonField
     *
     * @return Response
     */
    public function edit(Request $request, JsonField $jsonField): Response
    {
        $form = $this->createForm(JsonFieldType::class, $jsonField);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Field updated');

            // Update the related Json schema
            $this->jsonSchemaService->getJsonFromFields($jsonField->getJsonSchema());

            $required_schema = $request->query->get('schema');
            if (!empty($required_schema)) {
                // Back to the schema edition page
                return $this->redirectToRoute('json_schema_edit', ['id' => $required_schema]);
            }

            return $this->redirectToRoute('json_field_index', ['id' => $jsonField->getId()]);
        }

        return $this->render(
            'json_field/edit.html.twig',
            [
                'json_field' => $jsonField,
                'form' => $form->createView(),
            ]
        );
    }
}
