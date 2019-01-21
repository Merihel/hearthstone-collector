<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Entity\User;
use App\Entity\Deck;
use Psr\Log\LoggerInterface;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DeckController extends AbstractController
{
    /**
     * @Route("/deck/select/{id}", name="deck")
     */
    public function getDeckAction($id, Container $container)
    {
        $serializer = $container->get('jms_serializer');

        $deck = $this->getDoctrine()
            ->getRepository(Deck::class)
            ->find($id);

        if (!$deck) {
            throw $this->createNotFoundException(
                'No deck found for id '.$id
            );
        }

        $jsonObject = $serializer->serialize($deck, 'json');
        return $this->json(json_decode($jsonObject));
    }

    /**
     * @Route("/deck/select-by-user/{id}")
     */
    public function getDecksByUser($id, Container $container)
    {
        $serializer = $container->get('jms_serializer');

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        return $this->json(json_decode($serializer->serialize($user->getDecks(), 'json')));
    }

    /**
     * @Route("/deck/new")
     */
    public function newDeckAction(Request $request)
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)
        $entityManager = $this->getDoctrine()->getManager();
        $deck = new Deck();

        // + liste de cartes
        $deck->setName($request->request->get('name'));
        $deck->setDescription($request->request->get('description'));

        // tell Doctrine you want to (eventually) save the User (no queries yet)
        $entityManager->persist($deck);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->json([
            'message' => 'Successfully saved deck',
            'id' => $deck->getId(),
            'devMessage' => "Success : nothing to show here",
        ]);
    }
}
