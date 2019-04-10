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
    //Récupérer un deck par son id
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

    //Récupérer tous les decks d'un utilisateur
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

    //Créer un deck, il faut renseigner par JSON tous leschamps obligatoires, JMS serialisera le JSON en objet Deck
    /**
     * @Route("/deck/new")
     */
    public function newDeckAction(Request $request, Container $container)
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)

        $entityManager = $this->getDoctrine()->getManager();
        $serializer = $container->get('jms_serializer');

        $json = json_decode($request->getContent(), true);
        $user = $this->getDoctrine()->getRepository(User::class)->find($json["user_id"]);   
        if ($user != null) {
            $deck = new Deck($json["name"], $json["description"], $user);
        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Utilisateur introuvable',
                'devMessage' => "ERROR_DECK_NOT_SAVED",
            ]);
        }

        if($this->createDeck($deck)) {
            $user->addDeck($deck);
            $entityManager->merge($user);
            $entityManager->flush();

            return $this->json([
                'exit_code' => 0,
                'message' => 'Deck enregistré',
                'id' => $deck->getId(),
                'devMessage' => "Success : nothing to show here",
            ]);
        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Erreur lors de la création du deck'.$deck->getId(),
                'devMessage' => "ERROR_DECK_NOT_SAVED",
            ]);
        }
    }

    //La fonction pour persist un deck
    public function createDeck($deck) {
        $em = $this->getDoctrine()->getManager();
        $em->persist($deck);
        
        try {
            $em->flush();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    //Met à jour un deck, toujours la serialisation par JMS
    /**
    * @Route("/deck/update")
    */

    public function updateDeckAction(Request $request, Container $container)
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)
        $serializer = $container->get('jms_serializer');

        $deck = null;
        try {
            $deck = $serializer->deserialize($request->getContent(), 'App\Entity\Deck', 'json');

            if ($deck != null) {
                $didUpdate = $this->updateDeck($deck);
            }
        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Erreur lors de l\'envoi des données',
                'devMessage' => 'Error deserializing JSON: '.$request->getContent(),
            ]);
        }

        switch($didUpdate) {
            case 0:
                return $this->json([
                    'exit_code' => 0,
                    'message' => 'Deck mis à jour !',
                    'devMessage' => "Success : nothing to show here",
                ]);
                break;
            case 1:
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Deck non trouvé',
                    'devMessage' => 'Error updating deck with id '.$deck->getId().': deck not found'
                ]);
                break;
            case 2:
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Erreur lors de la mise à jour',
                    'devMessage' => 'Error updating deck with id '.$deck->getId().': database update error'
                ]);
                break;
        }

    }

    //Fonction d'update de deck puis persistence en BDD
    public function updateDeck($deck) {

        $em = $this->getDoctrine()->getManager();

        $lastDeck = $this->getDoctrine()
            ->getRepository(Deck::class)
            ->findBy(array('id' => $deck->getId()));

        if ($lastDeck != null) {
            if($deck->getName() != null) 
                $lastDeck[0]->setName($deck->getName());
            if($deck->getDescription() != null) 
                $lastDeck[0]->setDescription($deck->getDescription());
            if($deck->getCardsList() != null){

                foreach($deck->getCardsList() as &$card){
                    $lastDeck[0]->removeCardsList($card);
                }


                foreach($deck->getCardsList() as &$card){
                    $lastDeck[0]->addCardsList($card);
                }

            }
                
        } else {
            return 1;
        }        

        try {
            // tell Doctrine you want to (eventually) update the Deck (no queries yet). The deck null fields are stripped
            $em->merge($lastDeck[0]);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return 1;
        }

        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return 0;
        } catch (\Doctrine\ORM\ORMException $e) {
            return 2;
        }
    }

    //Suppression d'un deck
    /**
    * @Route("/deck/delete/{id}")
    */
    public function deleteDeckAction(Request $request, $id, Container $container) {
        $em = $this->getDoctrine()->getManager();
        $serializer = $container->get('jms_serializer');
        $deck = $this->getDoctrine()->getRepository(Deck::class)->find($id);        

        if (!$deck){
            return $this->json([
                'exit_code' => 500,
                'message' => 'Pas de deck trouvé',
                'devMessage' => 'INVALID_DECK',
            ]);
        } else {
            // on supprime la relation user-deck
            $user = $deck->getUserId();
            $user->removeDeck($deck);
            $em->persist($user);
            // On supprime le deck
            $em->remove($deck);

            $em->flush();

            return $this->json([
                'exit_code' => 200,
                'message' => 'Deck supprimé',
                'devMessage' => 'SUCCESS',
            ]);
        }


      }

}
