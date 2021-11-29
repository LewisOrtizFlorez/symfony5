<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="homepage", methods={"GET"})
     */
    public function index(Request $request, ConferenceRepository $conferenceRepository)
    {
        $request->getSession()->set('saludo', 'Hola mundo');
        // dd($request->getSession()->get('saludo'));
        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
    }

    /**
     * @Route("/conference/{slug}", name="conference", methods={"GET", "POST", "DELETE", "PUT"})
     */
    public function show(Request $request, CommentRepository $commentRepository, Conference $conference, SpamChecker $spamChecker, string $photoDir)
    {

        $comment = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);

        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setConference($conference);

            $photo = $commentForm['photo']->getData();
            
            if($photo){
                $fileName = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                try{
                    $photo->move($photoDir, $fileName);
                }catch(FileException $e){

                }

                $comment->setPhoto($fileName);
            }

            $this->entityManager->persist($comment);

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            
            if("is spam" === $spamChecker->getSpamCore($comment, $context)){
               throw new \RuntimeException('Comment is spam, go away!');
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0)) ;
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATION_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATION_PER_PAGE),
            'commentForm' => $commentForm->createView(),
        ]);
    }
}
