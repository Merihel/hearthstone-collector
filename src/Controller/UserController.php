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
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Route("/user/select-by-mail/{mail}")
     */
    public function getUserByMailAction($mail, Container $container)
    {
        //Container possède les services, not celui de JMS
        //Je créé un objet JMSSerializer pour la sérialisation/déserialisation
        $serializer = $container->get('jms_serializer');

        //Je déclare un objet User que je récupère grâce au manager de Doctrine, qui utilise le repository de mon User
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(array('mail' => $mail));

        //Si je n'ai pas d'user, je lève une exception
        if (!$user[0]) {
            throw $this->createNotFoundException(
                'No user found for mail '.$mail
            );
        } else {
            //Sinon je créé mon objet JSON via la fonction "serialize" de JMS, et l'envoie en front
            $jsonObject = $serializer->serialize($user[0], 'json');
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

    //ROUTES DE LOGIN

    /**
     * @Route("/user/login")
     */
    public function loginAction(Request $request, Container $container)
    {
        $serializer = $container->get('jms_serializer');
        $json = json_decode($request->getContent(), true);
        $identifier = $json["identifier"];
        $password = $json["password"];
        if ($this->checkEmailStruct($identifier)) { //if it's a mail
            if($this->doesMailExists($identifier)) {
                $user = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->findBy(array('mail' => $identifier));
                if ($user[0] !=null && $this->checkPasswordConcordance($user[0], $password)) {
                    $jsonObject = $serializer->serialize($user[0], 'json');
                    return $this->json(json_decode($jsonObject));
                } else {
                    return $this->json([
                        'exit_code' => 500,
                        'message' => 'Mot de passe invalide pour le mail donné',
                        'devMessage' => 'INVALID_PASSWORD',
                    ]);
                }
            } else {
                return $this->json([
                    'exit_code' => 500,
                    'message' => 'Utilisateur introuvable',
                    'devMessage' => 'UNKNOWN_EMAIL',
                ]);
            }
        } else { //if it's a pseudo
            if($this->doesPseudoExists($identifier)) {
                //user with pseudo "$identifier" found
                $user = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->findBy(array('pseudo' => $identifier));
                if ($user[0] !=null && $this->checkPasswordConcordance($user[0], $password)) {
                    $jsonObject = $serializer->serialize($user[0], 'json');
                    return $this->json(json_decode($jsonObject)); //On retourne l'user car le mot de passe est bon
                } else {
                    return $this->json([
                        'exit_code' => 500,
                        'message' => 'Mot de passe invalide pour le pseudo donné',
                        'devMessage' => 'INVALID_PASSWORD',
                    ]);
                }
            } else {
                return $this->json([
                    'exit_code' => 500,
                    'message' => 'Utilisateur introuvable',
                    'devMessage' => 'UNKNOWN_PSEUDO',
                ]);
            }
        }

        return $this->json([
            'exit_code' => 500,
            'message' => 'Une erreur interne est survenue',
            'devMessage' => 'ERR_SHOULD_NOT_BE_HERE',
        ]);
    }

    //FONCTION DE CHECK DE MAIL, EN ROUTE ET EN FONCTION UTIL

    /**
     * @Route("/user/check-mail/{mail}")
     */
    public function checkUserMailAction($mail)
    {
        if ($this->doesMailExists($mail)) {
            return $this->json([
                'exit_code' => 0,
                'message' => 'Cet email existe déjà',
                'devMessage' => 'Success : nothing to show here',
            ]);
        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Cet email est inconnu',
                'devMessage' => 'UNKNOWN_EMAIL',
            ]);
        }
    }

    public function doesMailExists($mail) {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(array('mail' => $mail));

        if ($user == null) {
            return false;
        } else {
            return true;
        }
    }

    function checkEmailStruct($email) {
        return (strpos($email, '@'));
     }

    //FONCTION DE CHECK DE PSEUDO, EN ROUTE ET EN FONCTION UTIL

    /**
     * @Route("/user/check-pseudo/{pseudo}")
     */
    public function checkUserPseudoAction($pseudo)
    {
        if ($this->doesPseudoExists($pseudo)) {
            return $this->json([
                'exit_code' => 0,
                'message' => 'Ce pseudo existe déjà',
                'devMessage' => 'Success : nothing to show here',
            ]);
        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Ce pseudo est inconnu',
                'devMessage' => 'UNKNOWN_USERNAME',
            ]);
        }
    }

    public function doesPseudoExists($pseudo) {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(array('pseudo' => $pseudo));
        
        if($user == null) {
            return false;
        } else {
            return true;
        }
    }

    //FONCTION DE CHECK DE MOT DE PASSE
    public function checkPasswordConcordance($user, $password) {
        return $user->getPassword() == $password ? true : false;
    }

    /**
     * @Route("/user/sync1")
     */
    public function synchronizeUserActionStep1(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $mail = $json["mail"];
        if($this->doesMailExists($mail)) {
            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->findBy(array('mail' => $mail));
            if($user[0]->getPseudo() != null && $user[0]->getPseudo() != "") {
                return $this->json([
                    'exit_code' => 0,
                    'message' => 'Pseudo trouvé: Connexion en cours...',
                    'devMessage' => 'Success : nothing to show here',
                ]);
            } else {
                return $this->json([
                    'exit_code' => 2,
                    'message' => 'Un pseudo est nécessaire',
                    'devMessage' => 'ERROR_PSEUDO_NEEDED',
                ]);
            }
        } else {
            return $this->json([
                'exit_code' => 3,
                'message' => 'Compte prêt à être créé, un pseudo est nécessaire',
                'devMessage' => 'ERROR_READY_TO_CREATE_PSEUDO_NEEDED',
            ]);
        }
    }

    /**
     * @Route("/user/sync2/{arg}")
     */
    public function synchronizeUserActionStep2(Request $request, Container $container, $arg)
    {
        $serializer = $container->get('jms_serializer');
        $userToBeCreated = $serializer->deserialize($request->getContent(), 'App\Entity\User', 'json');

        $userToBeCreated->setPassword("NONE");

        if ($arg == "create") {
            if($this->createUser($userToBeCreated)) {
                return $this->json([
                    'exit_code' => 0,
                    'message' => 'Utilisateur enregistré au compte social',
                    'devMessage' => 'Success : nothing to show here',
                ]);
            } else {
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Impossible d\'enregistrer le compte',
                    'devMessage' => 'ERROR_USER_NOT_CREATED',
                ]);
            }
        } else if ($arg == "update") {

            if($this->updateUser($userToBeCreated) == 0) {
                return $this->json([
                    'exit_code' => 0,
                    'message' => 'Utilisateur enregistré au compte social',
                    'devMessage' => 'Success : nothing to show here',
                ]);
            } else {
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Impossible d\'enregistrer le nuveau compte social',
                    'devMessage' => 'ERROR_USER_NOT_CREATED',
                ]);
            }
        }
        
    }   

    //CREATION D'UN NOUVEL USER

    /**
     * @Route("/user/new")
     */
    public function newUserAction(Request $request, Container $container, LoggerInterface $logger)
    {
        $logger->info('REQUEST JSON: '.$request->getContent());
        $serializer = $container->get('jms_serializer');
        //Deserialize json from HTTP POST into a valid User object
        $user = $serializer->deserialize($request->getContent(), 'App\Entity\User', 'json');


        if($this->doesMailExists($user->getMail())) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Ce mail existe déjà',
                'devMessage' => "ERROR_MAIL_ALREADY_EXISTS",
            ]);
        }

        if($this->doesPseudoExists($user->getPseudo())) {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Ce pseudo est déjà pris, désolé !',
                'devMessage' => "ERROR_PSEUDO_ALREADY_EXISTS",
            ]);
        }

        if($this->createUser($user)) {
            return $this->json([
                'exit_code' => 0,
                'message' => 'Utilisateur  enregistré',
                'devMessage' => "Success : nothing to show here",
            ]);
        } else {
            return $this->json([
                'exit_code' => 1,
                'message' => 'Erreur lors de l\'enregistrement de l\'utilisateur '.$user->getId(),
                'devMessage' => "ERROR_USER_NOT_SAVED",
            ]);
        }
    }

    public function createUser($user) {
        if($user->getCoins() == null || $user->getCoins() == 0) {
            $user->setCoins(75);
        }

        //Here the user password SHOULD BE encrypted with a bcrypt algorithm

        $em = $this->getDoctrine()->getManager();
        // tell Doctrine you want to (eventually) save the User (no queries yet)
        $em->persist($user);
        
        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();
            return true;
        } catch (Exception $e) {
            return false;
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
        $serializer = $container->get('jms_serializer');

        $user = null;
        try {
            //Deserialize json from HTTP POST into a valid User object
            $user = $serializer->deserialize($request->getContent(), 'App\Entity\User', 'json');

            if ($user != null) {
                $didUpdate = $this->updateUser($user);
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
                    'message' => 'Utilisateur mis à jour !',
                    'devMessage' => "Success : nothing to show here",
                ]);
                break;
            case 1:
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Utilisateur non trouvé',
                    'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
                ]);
                break;
            case 2:
                return $this->json([
                    'exit_code' => 1,
                    'message' => 'Erreur lors de la mise à jour',
                    'devMessage' => 'Error updating user with id '.$user->getId().': database update error'
                ]);
                break;
        }

    }

    public function updateUser($user) {

        $em = $this->getDoctrine()->getManager();

        $lastUser = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(array('mail' => $user->getMail()));

        if ($lastUser != null) {
            if($user->getFacebookId() != null) 
                $lastUser[0]->setFacebookId($user->getFacebookId());
            if($user->getGoogleId() != null) 
                $lastUser[0]->setGoogleId($user->getGoogleId());
            if($user->getPseudo() != null) 
                $lastUser[0]->setPseudo($user->getPseudo());
            if($user->getCoins() != null) 
                $lastUser[0]->setCoins($user->getCoins());
        } else {
            return 1;
        }
        

        try {
            // tell Doctrine you want to (eventually) update the User (no queries yet). The user null fields are stripped
            $em->merge($lastUser[0]);
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
