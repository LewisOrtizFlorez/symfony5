<?php

namespace App;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{
  private $client;
  private $endpoint;
    public function __construct(HttpClientInterface $client, string $akismetKey)
    {
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $akismetKey);
    }

    public function getSpamCore(Comment $comment, array $context){

      $response = $this->client->request('POST', $this->endpoint, [
        'body' => array_merge($context, [
          'blog' => 'localhost:82',
          'comment_type' => 'comment',
          'comment_author' => $comment->getAuthor(),
          'comment_author_email' => $comment->getEmail(),
          'comment_content' => $comment->getText(),
          'comment_date_get' => $comment->getCreatedAt()->format('c'),
          'blog_lang' => 'en',
          'blog_charset' => 'UTF-8',
          'is_test' => true,
        ]),
      ]);

      $headers = $response->getHeaders();

      if(($headers['X-akismet-pro-tip'][0] ?? '') === 'discard'){
        return "is spam";
      }

      $content = $response->getContent();
      if(isset($headers['x-akismet-debug-help'][0])){
        throw new \RuntimeException(sprintf('Unable to check for spam: %s ($s)', $content, $headers['x-akismet-debug-help'][0]));
      }
      
      return $content === 'true' ? 'is spam' : 'is not spam';
    }
}