<?php

namespace App\Controller;

use App\Entity\JsonField;
use App\Entity\JsonSchema;
use App\Form\JsonFieldType;
use App\Repository\JsonFieldRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
     * JsonSchemaService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="json_field_index")
     * @param Request $request
     * @param JsonFieldRepository $json_fieldRepository
     * @param SerializerInterface $serializer
     *
     * @return Response
     */
    public function index(Request $request,
                          JsonFieldRepository $json_fieldRepository,
                          SerializerInterface $serializer)
    {
        $required_schema_name = $request->query->get('schema');
        $this->logger->info("Required schema: " . $required_schema_name);

        $json_fields = [];
        if (! empty($request->query->get('schema'))) {
            $repository = $this->getDoctrine()->getRepository(JsonSchema::class);
            $schema = $repository->findOneBy(['name' => $required_schema_name]);
            $this->logger->info("Schema: " . $schema->getName());
            if ($schema) {
                $json_fields = $schema->getJsonFields();
            }
        } else {
            $json_fields = $json_fieldRepository->findAll();
        }

        // Create a string array to configure the JsTree
        $jsonContent = [];
        foreach ($json_fields as $field) {
            $this->logger->info("*** ->: " . $field->getName());

            // The text field is displayed on the UI
            $new_field = [];
            $new_field['id'] = $field->getId();
            $new_field['text'] = $field->getName();
            $new_field['type'] = $field->getType();
            $new_field['format'] = $field->getFormat();
            $new_field['parent'] = '#';
            if ($field->getParent()) {
                $new_field['parent'] = (string)$field->getParent()->getId();
            }
            $jsonContent[] = $new_field;

            $this->logger->info("*** ->: " . json_encode($new_field));
        }

        return $this->render('json_field/index.html.twig', [
            'controller_name' => 'JsonFieldController',
            'items' => $json_fields,
            'json_content' => json_encode($jsonContent),
        ]);
    }

    /**
     * @Route("/new", name="json_field_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $json_field = new JsonField();
        $form = $this->createForm(JsonFieldType::class, $json_field);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($json_field);
            $em->flush();

            return $this->redirectToRoute('json_field_index');
        }

        return $this->render(
            'json_field/new.html.twig',
            [
                'json_field' => $json_field,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="json_field_delete", methods="DELETE")
     *
     * @param Request    $request
     * @param JsonField $json_field
     *
     * @return Response
     */
    public function delete(Request $request, JsonField $json_field): Response
    {
        if ($this->isCsrfTokenValid('delete'.$json_field->getId(),
            $request->request->get('_token'))) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->remove($json_field);
                $em->flush();
            } catch (ForeignKeyConstraintViolationException $exp) {
                $this->addFlash(
                    'danger',
                    'Deleting a node with children is forbidden!'
                );
            }
        }

        return $this->redirectToRoute('json_field_index');
    }

    /**
     * @Route("/{id}", name="json_field_show", methods="GET")
     * @Route("/{id}/show", name="json_field_show", methods="GET")
     *
     * @param JsonField $json_field
     *
     * @return Response
     */
    public function show(JsonField $json_field): Response
    {
        return $this->render('json_field/show.html.twig', ['json_field' => $json_field]);
    }

    /**
     * @Route("/{id}/edit", name="json_field_edit", methods="GET|POST")
     *
     * @param Request $request
     * @param JsonField $json_field
     * @param LoggerInterface $logger
     *
     * @return Response
     */
    public function edit(Request $request, JsonField $json_field, LoggerInterface $logger): Response
    {
        $logger->info("Edit a json_field");
        $form = $this->createForm(JsonFieldType::class, $json_field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('json_field_index', ['id' => $json_field->getId()]);
        }

        return $this->render(
            'json_field/edit.html.twig',
            [
                'json_field' => $json_field,
                'form' => $form->createView(),
            ]
        );
    }

}
