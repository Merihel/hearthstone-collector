<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Request;


class UserController extends AbstractController
{   
    /**
     * @Route("/user/{id}")
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
            return $this->json($jsonObject);
        }
    }
    
    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
    
    /**
     * @Route("/new-user")
     */
    public function newUserAction(Request $request, Container $container)
    {

        $em = $this->getDoctrine()->getManager();
        $serializer = $container->get('jms_serializer');
        //Deserialize json from HTTP POST into a valid User object
        $user = $serializer->deserialize($request->request->get('json'), 'App\Entity\User', 'json');
        
        // tell Doctrine you want to (eventually) save the User (no queries yet)
        $em->persist($user);

        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();
            
            return $this->json([
                'state' => 'SUCCESS',
                'message' => 'Utilisateur enregistré',
                'id' => $user->getId(),
            ]);
        } catch (Exception $e) {
            return $this->json([
                'state' => 'ERROR',
                'message' => 'Erreur lors de l\'enregistrement de l\'utilisateur',
                'id' => $user->getId(),
                'devMessage' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * @Route("/update-user")
     */
    public function updateUserAction(Request $request, Container $container)
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)
        $em = $this->getDoctrine()->getManager();
        $serializer = $container->get('jms_serializer');
        
        try {
            //Deserialize json from HTTP POST into a valid User object
            $user = $serializer->deserialize($request->request->get('json'), 'App\Entity\User', 'json');

        } catch (\JMS\Serializer\Exception\RuntimeException $e) {
            return $this->json([
                'state' => 'ERROR',
                'message' => 'Erreur lors de l\'envoi des données',
                'devMessage' => 'Error deserializing JSON: '.$request->request->get('json'),
            ]);
        }
        
        try {
            // tell Doctrine you want to (eventually) update the User (no queries yet)
            $em->merge($user);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return $this->json([
                'state' => 'ERROR',
                'message' => 'Utilisateur non trouvé',
                'devMessage' => 'Error updating user with id '.$user->getId().': user not found'
            ]);
        }
        
        try {
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->json([
                'state' => 'SUCCESS',
                'message' => 'Utilisateur mis à jour !',
                'id' => $user->getId(),
            ]);
        } catch (\Doctrine\ORM\ORMException $e) {
            return $this->json([
                'state' => 'ERROR',
                'message' => 'Erreur lors de la mise à jour',
                'devMessage' => 'Error updating user with id '.$user->getId().': databse update error'
            ]);
        }

    }
}