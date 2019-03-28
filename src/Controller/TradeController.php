<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Entity\User;
use App\Entity\Trade;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TradeController extends AbstractController
{ 
    /**
     * @Route("/trade/new")
     */
    public function addTradeAction(Request $request, Container $container)
    { 
        $serializer = $container->get('jms_serializer');
        $json = json_decode($request->getContent(), true);
        if (isset($json["userAsker"]) && isset($json["userAsked"]) && isset($json["cardAsker"]) && isset($json["cardAsked"])) {
            $userAsker = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($json["userAsker"]);

            $userAsked = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($json["userAsked"]);

            $cardAsker = $this->getDoctrine()
            ->getRepository(Card::class)
            ->find($json["cardAsker"]);

            $cardAsked = $this->getDoctrine()
            ->getRepository(Card::class)
            ->find($json["cardAsked"]);

            if (!$this->isCardAlreadyPromised($cardAsker)) {
                $trade = new Trade($userAsker, $userAsked, $cardAsker, $cardAsked);

                $em = $this->getDoctrine()->getManager();
                $em->persist($trade);
                $em->flush();

                return $this->json([
                    'exit_code' => 200,
                    'message' => 'Echange en attente de réponse',
                    'devMessage' => 'OK',
                ]);

            } else {
                return $this->json([
                    'exit_code' => 500,
                    'message' => 'Carte déjà en échange',
                    'devMessage' => 'CARD_CURRENTLY_IN_TRADE',
                ]);
            }
        }
    }

    /**
     * @Route("/trade/select/{id}")
     */
    public function getTradesByUserAction($id, Request $request, Container $container) {
        $serializer = $container->get('jms_serializer');

        $myTrades = $this->getDoctrine()
        ->getRepository(Trade::class)
        ->findBy(array('userAsker' => $id));

        $otherTrades = $this->getDoctrine()
        ->getRepository(Trade::class)
        ->findBy(array('userAsked' => $id));

        if (isset($myTrades[0])) {
           
            if (isset($otherTrades[0])) {
                $allTrades = array_merge($myTrades, $otherTrades);
                $jsonObject = $serializer->serialize($allTrades, 'json');
                return $this->json(json_decode($jsonObject));
            } else {
                $jsonObject = $serializer->serialize($myTrades, 'json');
                return $this->json(json_decode($jsonObject));
            } 
        } else if (isset($otherTrades[0])) {
            $jsonObject = $serializer->serialize($otherTrades, 'json');
            return $this->json(json_decode($jsonObject));
        } else {
            return $this->json([
                'exit_code' => 500,
                'message' => 'Aucun trade trouvé',
                'devMessage' => 'NO_TRADE_FOUND',
            ]);
        }
    }
    
    public function isCardAlreadyPromised(Card $card) { //check si la card est déjà promise à quelqu'un donc de type "cardAsker" et check sur trades en cours (pas de check sur out et ok car user l'a forcément re-obtenue entre temps pour créer un trade, donc c'est bon)
        $trade = $this->getDoctrine()
        ->getRepository(Trade::class)
        ->findBy(array('cardAsker' => $card, 'status' => 'PENDING'));

        return isset($trade[0]);
    }

    //TODO update le status

    //TODO isOkey du asker et isOk du asked
}