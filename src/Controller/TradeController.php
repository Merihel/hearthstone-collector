<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Entity\User;
use App\Entity\Trade;
use App\Controller\UserController;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TradeController extends Controller
{ 
    //Permet de créer un nouvel échange
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

    //permet de sélectionner un échange par son id
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
                $allTrades = array_merge($myTrades, $otherTrades); //Si on a des trades en tant que "userAsked" ils s'ajoutent
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
    
    //permet de savoir si une carte est déjà promise en échange, retourne true ou false
    public function isCardAlreadyPromised(Card $card) { //check si la card est déjà promise à quelqu'un donc de type "cardAsker" et check sur trades en cours (pas de check sur out et ok car user l'a forcément re-obtenue entre temps pour créer un trade, donc c'est bon)
        $trade = $this->getDoctrine()
        ->getRepository(Trade::class)
        ->findBy(array('cardAsker' => $card, 'status' => 'PENDING'));

        return isset($trade[0]);
    }

    //Mettre àjour le statut d'un échange, entre "OK" (accepté), "OUT" (refusé) et "PENDING" (en cours). S'il passe à OK on valide automatiquement le trade en échangeant les cartes des users.
    /**
     * @Route("/trade/updateStatus")
     */
    public function updateTradeStatusAction(Request $request, Container $container)
    { 
        $serializer = $container->get('jms_serializer');
        $json = json_decode($request->getContent(), true);
        if (isset($json["id"]) && isset($json["status"])) {
            $trade = $this->getDoctrine()
                ->getRepository(Trade::class)
                ->find($json["id"]);

            $status = $json["status"];
            if ($status == "OK" || $status == "PENDING" || $status == "OUT") {
                $trade->setStatus($json["status"]);
                $status == "OK" ? $trade->setIsAskedOk(true) : $trade->setIsAskedOk(true);
                $em = $this->getDoctrine()->getManager();
                $em->merge($trade);
                $em->flush();

                if ($status == "OK") {
                    $finishReturn = $this->finishTrade($trade);

                    if (!$finishReturn) {
                        return $this->json([
                            'exit_code' => 500,
                            'message' => 'Erreur, impossible d\'échanger les cartes des utilisateurs',
                            'devMessage' => 'ERROR_CANT_SWAP_CARDS',
                        ]); 
                    }
                }

                return $this->json([
                    'exit_code' => 200,
                    'message' => 'Echange mis à jour',
                    'devMessage' => 'OK',
                ]); 
            } else {
                return $this->json([
                    'exit_code' => 500,
                    'message' => 'Erreur interne: status inconnu',
                    'devMessage' => 'UNKNOWN_STATUS',
                ]);
            }
        } else {
            return $this->json([
                'exit_code' => 500,
                'message' => 'Erreur interne: données introuvables',
                'devMessage' => 'DATA_NOT_FOUND_ID_OR_STATUS_UNKNOWN',
            ]);
        }
    }

    //Fonction qui termine le trade et qui échange les cartes en utilisant le UserController en tant que service
    function finishTrade(Trade $trade) {
        $userAsker = $trade->getUserAsker();
        $userAsked = $trade->getUserAsked();
        $cardAsker = $trade->getCardAsker();
        $cardAsked = $trade->getCardAsked();
        
        $userController = $this->get('user_controller_service');

        if ($userController->removeCardOfUser($userAsker, $cardAsker) &&  $userController->removeCardOfUser($userAsked, $cardAsked)) {
            $em = $this->getDoctrine()->getManager();
            $userAsker->addCard($cardAsked);
            $em->merge($userAsker);
            $em->flush();

            $userAsked->addCard($cardAsker);
            $em->merge($userAsked);
            $em->flush();

            return true;
        } else {
            return false;
        }
    }
}