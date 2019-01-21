<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Entity\User;
use App\Entity\Card;
use App\Entity\Deck;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializationContext;

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
            $stringResp = $stringResp . "<img src='".$value->getImgGold()."' />";
        }


        //return $this->json(json_decode($hsCards));
        return new Response($stringResp);
    }

    /**
     * @Route("/user/check-mail/{mail}")
     */
    public function checkUserMailAction($mail)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(array('mail' => $mail));

        if ($user == null) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Cet email est inconnu',
                'devMessage' => 'UNKNOWN_EMAIL',
            ]);
        } else {
            return $this->json([
                'exit_code' => 0,
                'message' => 'Cet email existe déjà',
                'devMessage' => 'Success : nothing to show here',
            ]);
        }
    }

    /**
     * @Route("/user/check-pseudo/{pseudo}")
     */
    public function checkUserPseudoAction($pseudo)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(array('pseudo' => $pseudo));

        if ($user == null) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Ce pseudo est inconnu',
                'devMessage' => 'UNKNOWN_USERNAME',
            ]);
        } else {
            return $this->json([
                'exit_code' => 0,
                'message' => 'Ce pseudo existe déjà',
                'devMessage' => 'Success : nothing to show here',
            ]);
        }

        return $this->json(json_decode($jsonObject));
    }

    /**
     * @Route("/user/sync")
     */
    public function synchronizeUserAction(Request $request, Container $container)
    {
        $mail = $request->request->get('mail');
        var_dump($mail);
        $jsonStr = $this->checkUserMailAction($mail);
        echo "<pre>";
        var_dump(json_decode($jsonStr));
        echo "</pre>";
        if(json_decode($this->checkUserMailAction($mail))["exit_code"] == 1) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'User with mail '.$mail. 'not found'
            ]);
        } else {
            return $this->json([
                'exit_code' => 0,
                'message' => 'User with mail '.$mail.' found',
            ]);
        }
    }   

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
                'exit_code' => 0,
                'message' => 'Utilisateur '.$user->getId().' enregistré',
                'devMessage' => "Success : nothing to show here",
            ]);
        } catch (Exception $e) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Erreur lors de l\'enregistrement de l\'utilisateur '.$user->getId(),
                'devMessage' => $e->getMessage(),
            ]);
        }
    }

    //TODO REPARER LE UPDATE

    //Attention sur le update : les champs manquant seront null ou vides dans la base de données !

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
                'exit_code' => 1,
                'message' => 'Erreur lors de l\'envoi des données',
                'devMessage' => 'Error deserializing JSON: '.$request->request->get('json'),
            ]);
        }

        try {
            // tell Doctrine you want to (eventually) update the User (no queries yet). The user null fields are stripped
            $em->merge($users);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Utilisateur non trouvé',
                'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
            ]);
        }

        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->json([
                'exit_code' => 0,
                'message' => 'Utilisateur mis à jour !',
                'devMessage' => "Success : nothing to show here",
            ]);
        } catch (\Doctrine\ORM\ORMException $e) {
            return $this->json([
                'exit_code' => 1,
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
                    'exit_code' => 1,
                    'message' => 'Utilisateur non trouvé',
                    'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
                ]);
            }

            try {
                // actually executes the queries (i.e. the INSERT query)
                $em->flush();

                return $this->json([
                    'exit_code' => 0,
                    'message' => 'Carte ajoutee a '.$user->getPseudo(),
                    'devMessage' => "Success : nothing to show here",
                ]);
            } catch (\Doctrine\ORM\ORMException $e) {
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Erreur lors de l\'ajout de la carte à l\'utilisateur',
                    'devMessage' => 'Error updating user with id '.$user->getId().' to set card '.$card->getId().' : database update error'
                ]);
            }

        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Utilisateur ou carte manquante',
                'devMessage' => 'Error while getting User/Card > missing one or both',
            ]);
        }
    }


    /**
     * @Route("/user/set-deck")
     */
    public function setDeckToUserAction(Request $request, Container $container) {
        $json = json_decode($request->request->get('json'), true);
        $deck = $this->getDoctrine()->getRepository(Deck::class)->find($json["deckId"]);
        $user = $this->getDoctrine()->getRepository(User::class)->find($json["id"]);
        $em = $this->getDoctrine()->getManager();

        if ($user && $deck) {
            $user->addDeck($deck);

            try {
                // tell Doctrine you want to (eventually) update the User (no queries yet)
                $em->merge($user);
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Utilisateur non trouvé',
                    'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
                ]);
            }

            try {
                // actually executes the queries (i.e. the INSERT query)
                $em->flush();

                return $this->json([
                    'exit_code' => 0,
                    'message' => 'Deck ajoute a '.$user->getPseudo(),
                    'devMessage' => "Success : nothing to show here",
                ]);
            } catch (\Doctrine\ORM\ORMException $e) {
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Erreur lors de l\'ajout du deck à l\'utilisateur',
                    'devMessage' => 'Error updating user with id '.$user->getId().' to set deck '.$deck->getId().' : database update error'
                ]);
            }

        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Utilisateur ou deck manquant',
                'devMessage' => 'Error while getting User/Deck > missing one or both',
            ]);
        }
    }
}
