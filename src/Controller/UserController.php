<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Entity\User;
use App\Entity\Card;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Request;


class UserController extends AbstractController
{   
    /**
     * @Route("/user/select/{id}")
     */
    public function getUserAction($id, Container $container)
    {
        //Container possède les services, not celui de JMS
        //Je créé un objet JMSSerializer pour la sérialisation/déserialisation
        $serializer = $container->get('jms_serializer');
        
        //Je déclare un objet User que je récupère grâce au manager de Doctrine, qui utilise le repository de mon User
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($id);
        
        //Si je n'ai pas d'user, je lève une exception
        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        } else {
            //Sinon je créé mon objet JSON via la fonction "serialize" de JMS, et l'envoie en front
            $jsonObject = $serializer->serialize($user, 'json');
            return $this->json(json_decode($jsonObject));
        }
    }
    
    
    /**
     * @Route("/user/select-with-cards/{id}", name="user")
     */
    /*
    public function selectWithCardsAction($id, Request $request, Container $container)
    {
        $serializer = $container->get('jms_serializer');
        $hearthstoneApiService = new HearthstoneApiService();


        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($id);

        $userCards = $user->getCards();
        $stringResp = "";
        
        foreach($userCards as $value) {
            $hsCard = $hearthstoneApiService->getCard($value->getHsId());
            $stringResp = $stringResp . "<img src='".$hsCard[0]->img."' />";
        }
        

        //return $this->json(json_decode($hsCards));
        return $this->json(json_decode((string) $userCards));
    }
    */

    /**
     * @Route("/user/new")
     */
    public function newUserAction(Request $request, Container $container, LoggerInterface $logger)
    {
        $logger->info('REQUEST JSON: '.$request->getContent());
        $em = $this->getDoctrine()->getManager();
        $serializer = $container->get('jms_serializer');
        //Deserialize json from HTTP POST into a valid User object
        $user = $serializer->deserialize($request->getContent(), 'App\Entity\User', 'json');
        
        if($user->getCoins() == null || $user->getCoins() == 0) {
            $user->setCoins(75); 
        }

        // tell Doctrine you want to (eventually) save the User (no queries yet)
        $em->persist($user);

        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();
            
            return $this->json([
                'status' => 'SUCCESS',
                'message' => 'Utilisateur '.$user->getId().' enregistré',
                'devMessage' => "Success : nothing to show here",
            ]);
        } catch (Exception $e) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'enregistrement de l\'utilisateur '.$user->getId(),
                'devMessage' => $e->getMessage(),
            ]);
        }
    }
    
    //TODO REPARER LE UPDATE

    /**
     * @Route("/user/update")
     */
    public function updateUserAction(Request $request, Container $container)
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)
        $em = $this->getDoctrine()->getManager();
        $serializer = $container->get('jms_serializer');
        $user = null;
        try {
            //Deserialize json from HTTP POST into a valid User object
            $user = $serializer->deserialize($request->request->get('json'), 'App\Entity\User', 'json');

        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi des données',
                'devMessage' => 'Error deserializing JSON: '.$request->request->get('json'),
            ]);
        }
        
        try {
            // tell Doctrine you want to (eventually) update the User (no queries yet)
            $em->merge($user);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Utilisateur non trouvé',
                'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
            ]);
        }
        
        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->json([
                'status' => 'SUCCESS',
                'message' => 'Utilisateur mis à jour !',
                'devMessage' => "Success : nothing to show here",
            ]);
        } catch (\Doctrine\ORM\ORMException $e) {
            return $this->json([
                'state' => 'ERROR',
                'message' => 'Erreur lors de la mise à jour',
                'devMessage' => 'Error updating user with id '.$user->getId().': database update error'
            ]);
        }

    }

    /**
     * @Route("/user/set-card")
     */
    public function setCardToUserAction(Request $request, Container $container) {
        $json = json_decode($request->request->get('json'), true);
        $card = $this->getDoctrine()->getRepository(Card::class)->find($json["cardId"]);
        $user = $this->getDoctrine()->getRepository(User::class)->find($json["id"]);
        $em = $this->getDoctrine()->getManager();

        if ($user && $card) {
            $user->addCard($card);

            try {
                // tell Doctrine you want to (eventually) update the User (no queries yet)
                $em->merge($user);
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => 'Utilisateur non trouvé',
                    'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
                ]);
            }

            try {
                // actually executes the queries (i.e. the INSERT query)
                $em->flush();
    
                return $this->json([
                    'status' => 'SUCCESS',
                    'message' => 'Carte ajoutee a '.$user->getPseudo(),
                    'devMessage' => "Success : nothing to show here",
                ]);
            } catch (\Doctrine\ORM\ORMException $e) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => 'Erreur lors de l\'ajout de la carte à l\'utilisateur',
                    'devMessage' => 'Error updating user with id '.$user->getId().' to set card '.$card->getId().' : database update error'
                ]);
            }

        } else {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Utilisateur ou carte manquante',
                'devMessage' => 'Error while getting User/Card > missing one or both',
            ]);
        }
    }
}