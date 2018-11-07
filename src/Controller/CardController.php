<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CardController extends AbstractController
{
    /**
     * @Route("/card/select/{id}", name="card")
     */
    public function getCardAction($id)
    {
        $card = $this->getDoctrine()
            ->getRepository(Card::class)
            ->find($id);
        
        if (!$card) {
            throw $this->createNotFoundException(
                'No card found for id '.$id
            );
        }
        
        $hearthstoneApiService = new HearthstoneApiService();
        $cardJson = $hearthstoneApiService->getCard($card->getHsId());
        
        return $this->json($cardJson[0]);
    }
    
    /**
     * @Route("/card/new")
     */
    public function newCardAction(Request $request)
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)
        $entityManager = $this->getDoctrine()->getManager();
        $card = new Card();
        $card->setHsId($request->request->get('hsId'));
        $card->setCost($request->request->get('cost'));

        // tell Doctrine you want to (eventually) save the User (no queries yet)
        $entityManager->persist($card);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->json([
            'message' => 'Successfully saved card',
            'id' => $card->getId(),
            'devMessage' => "Success : nothing to show here",
        ]);
    }
}
