<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Entity\User;
use App\Entity\Friendship;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/*
* @View(serializerEnableMaxDepthChecks=true)
*/
class FriendshipController extends AbstractController
{
    /**
     * @Route("/friendship/add")
     */
    public function addFriendshipAction(Request $request, Container $container)
    {
        $serializer = $container->get('jms_serializer');
        $json = json_decode($request->getContent(), true);
        if (isset($json["user1"]) && isset($json["user2"])) {
            $user1 = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($json["user1"]);

            $user2 = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($json["user2"]);

            $friendship = new Friendship($user1, $user2, $json["isAccepted"]);

            if ($this->friendshipAlreadyExists($friendship)) {
                return $this->json([
                    'exit_code' => 500,
                    'message' => 'Vous êtez déjà ami',
                    'devMessage' => 'FRIENDSHIP_ALREADY_EXISTS',
                ]);
            }
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($friendship);
            $em->flush();
            
            return $this->json([
                'exit_code' => 200,
                'message' => 'Ami ajouté',
                'devMessage' => 'SUCCESS',
            ]);
        } else {
            return $this->json([
                'exit_code' => 500,
                'message' => 'Impossible d\'ajouter cet ami',
                'devMessage' => 'CANT_FIND_IDS_IN_JSON',
            ]);
        }
    }

    public function friendshipAlreadyExists(Friendship $friendship) {
        $test1Passed = false;
        $test2Passed = false;

        $testFriendship1 = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findBy(array('user1' => $friendship->getUser1(), 'user2' => $friendship->getUser2()));

        if ($testFriendship1 == null) {
            $test1Passed = false;
        } else {
            $test1Passed = true;
        }

        $testFriendship2 = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findBy(array('user1' => $friendship->getUser2(), 'user2' => $friendship->getUser1()));
        
        if ($testFriendship2 == null) {
            $test2Passed = false;
        } else {
            $test2Passed = true;
        }
        if ($test1Passed) {
            return true;
        } else if ($test2Passed) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @Route("/friendship/selectByUser/{id}")
     */
    public function getFriendshipAction(Request $request, Container $container, $id)
    {
        $serializer = $container->get('jms_serializer');

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findById($id);
        
        $firstList = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findBy(array('user1' => $user, 'isAccepted' => true));
        
        /*
        for($i=0; $i<count($firstList); $i++) {
            echo $firstList[$i]->getUser1()->getPseudo() ." => ". $firstList[$i]->getUser2()->getPseudo() . " \n , ";
        }
        */

        $secondList = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findBy(array('user2' => $user, 'isAccepted' => true));

        for($i=0; $i<count($secondList); $i++) {
            $secondList[$i] = $this->reverseUsers($secondList[$i]);
        }

        $finalArray = array_merge($firstList, $secondList);

        /*
        for($i=0; $i<count($finalArray); $i++) {
            echo $finalArray[$i]->getUser1()->getPseudo() ." => ". $finalArray[$i]->getUser2()->getPseudo() . " \n , ";
        }
        */

        return $this->json(json_decode($serializer->serialize($finalArray, 'json')));
    }

    /**
     * @Route("/friendship/selectByUserPending/{id}")
     */
    public function getPendingFriendshipAction(Request $request, Container $container, $id)
    {
        $serializer = $container->get('jms_serializer');

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findById($id);
        
        $firstList = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findBy(array('user1' => $user, 'isAccepted' => false));
        
        /*
        for($i=0; $i<count($firstList); $i++) {
            echo $firstList[$i]->getUser1()->getPseudo() ." => ". $firstList[$i]->getUser2()->getPseudo() . " \n , ";
        }
        */

        $secondList = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findBy(array('user2' => $user, 'isAccepted' => false));

        for($i=0; $i<count($secondList); $i++) {
            $secondList[$i] = $this->reverseUsers($secondList[$i]);
        }

        $finalArray = array_merge($firstList, $secondList);

        /*
        for($i=0; $i<count($finalArray); $i++) {
            echo $finalArray[$i]->getUser1()->getPseudo() ." => ". $finalArray[$i]->getUser2()->getPseudo() . " \n , ";
        }
        */

        return $this->json(json_decode($serializer->serialize($finalArray, 'json')));
    }
    

    public function reverseUsers(Friendship $friendship) {
        $usr1 = $friendship->getUser1();
        $usr2 = $friendship->getUser2();
        $friendship->setUser1($usr2);
        $friendship->setUser2($usr1);
        return $friendship;
    }

    /**
     * @Route("/friendship/delete/{id}")
     */
    public function deleteFriendshipAction(Request $request, Container $container, $id)
    {
        $friendship = $this->getDoctrine()
            ->getRepository(Friendship::class)
            ->findById($id);

        if ($friendship[0] != null) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($friendship[0]);
            $em->flush();

            return $this->json([
                'exit_code' => 200,
                'message' => $friendship->getUser2()->getPseudo() . ' a bien été supprimé',
                'devMessage' => 'SUCCESS',
            ]);
        } else {
            return $this->json([
                'exit_code' => 500,
                'message' => 'Erreur: Cet ami n\'existe pas',
                'devMessage' => 'ERROR_FRIEND_NOT_FOUND',
            ]);
        }
    }
}