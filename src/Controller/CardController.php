<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Entity\User;
use Psr\Log\LoggerInterface;
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
    
    /*
    * 24 cartes = 30 secondes d'appel API ! Il faut changer et enegistrer les cartes sur l'app....
    */
    
    /**
     * @Route("/card/select-list")
     */
    public function getCardListAction(Request $request, LoggerInterface $logger) 
    {
        $logger->info('REQUEST JSON: '.$request->request->get("json"));
        $json = json_decode($request->request->get("json"), true);
        $jsonValues = $json["json"];
        $hearthstoneApiService = new HearthstoneApiService();
        /*
        echo '<pre>'; 
        var_dump($json); 
        echo '</pre>';
        */
        $imgArray = [];
        $html = "";
        for ($i=0; $i<count($jsonValues); $i++) {
            $hsId = $jsonValues[$i]["hsId"];
            $card = $hearthstoneApiService->getCard($hsId);
            array_push($imgArray, $card[0]->img);
            $html = $html . "<img src='".$imgArray[$i]."'><br>";
        }
        return new Response($html);
    }
    
    /**
     * @Route("/card/select-by-user/{id}")
     */
    public function getCardsByUser($id, Container $container) {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }
        
        echo $user->getCards()[0]->getId();
        
        return $this->json([
            'message' => 'Successfully got cards',
            'id' => $user->getId(),
            'devMessage' => "Success : nothing to show here",
        ]);
    }
    
    /**
     * @Route("/card/import/{hsId}")
     **/
    public function importCardAction($hsId)
    {
        $em = $this->getDoctrine()->getManager();
        $hearthstoneApiService = new HearthstoneApiService();
        $cardJson = $hearthstoneApiService->getCard($hsId);

        
        // $cardJson[0]->img
        $newCard = new Card();
        
        $newCard->setHsId($cardJson[0]->cardId);
        $newCard->setCost(isset($cardJson[0]->cost) ? $cardJson[0]->cost * 15 : 50);
        $newCard->setName(isset($cardJson[0]->name) ? $cardJson[0]->name : "");
        $newCard->setCardSet(isset($cardJson[0]->cardSet) ? $cardJson[0]->cardSet : "");
        $newCard->setType(isset($cardJson[0]->type) ? $cardJson[0]->type : "");
        $newCard->setFaction(isset($cardJson[0]->faction) ? $cardJson[0]->faction : "");
        $newCard->setRarity(isset($cardJson[0]->rarity) ? $cardJson[0]->rarity : "");
        $newCard->setText(isset($cardJson[0]->text) ? $cardJson[0]->text : "");
        $newCard->setFlavor(isset($cardJson[0]->flavor) ? $cardJson[0]->flavor : "");
        $newCard->setImg(isset($cardJson[0]->img) ? $cardJson[0]->img : "");
        $newCard->setImgGold(isset($cardJson[0]->imgGold) ? $cardJson[0]->imgGold : "");
        
        $em->persist($newCard);
        
        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();
            
            return $this->json([
                'status' => 'SUCCESS',
                'message' => 'Carte '.$newCard->getHsId().' enregistrée',
                'devMessage' => "Success : nothing to show here",
            ]);
        } catch (Exception $e) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'enregistrement de la carte '.$newCard->getHsId(),
                'devMessage' => $e->getMessage(),
            ]);
        }
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
