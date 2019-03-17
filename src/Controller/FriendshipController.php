<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Card;
use App\Entity\User;
use App\Entity\Friendship;
use Psr\Log\LoggerInterface;
use App\Service\HearthstoneApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
     * @Route("/friendship/selectAllFriendship")
     */


    
}