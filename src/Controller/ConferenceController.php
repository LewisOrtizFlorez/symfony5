<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
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
     * @Route("/conference/{id}", name="conference", methods={"GET"})
     */
    public function show(Request $request, CommentRepository $commentRepository, Conference $conference)
    {

        $offset = max(0, $request->query->getInt('offset', 0)) ;
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATION_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATION_PER_PAGE),
        ]);
    }
}
