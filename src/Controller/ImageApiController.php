<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ImageApiController extends AbstractController
{
    protected $filesystem;

    protected $entityManager;

    protected $passwordHasher;

    protected $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ) {
        $this->filesystem = new Filesystem();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    #[Route('/image', name: 'image_post', methods: ['POST'])]
    public function postImage(Request $request): Response
    {
        try {

            $image = $this->decodeImage($request->request->get('image'));

            $size = getimagesizefromstring($image);

            if ($size['mime'] != $this->getParameter('allowed_mime_type')) {
                throw new \Exception('Unsupported image type', Response::HTTP_FORBIDDEN);
            }

            if (!($size[0] > $this->getParameter('min_width') &&
                $size[1] > $this->getParameter('min_height')))
            {
                throw new \Exception('Unsupported image size', Response::HTTP_FORBIDDEN);
            }

            $folder = $this->getFolder();
            if (!$this->filesystem->exists($folder)) {
                $this->filesystem->mkdir($folder);
            }
            $fileId = (string)hrtime(TRUE);
            $filename = $fileId . '.' . $this->getFileExtension();
            $path = $folder . '/' . $filename;

            file_put_contents($path, $image);

            $this->log('Image uploaded: ' . $path);
            return $this->json([
                'url' => $request->getSchemeAndHttpHost() . '/image/' . $fileId,
            ]);
        }
        catch (\Exception $e) {
            $this->log($e->getMessage());
            return new Response($e->getMessage(), $e->getCode());
        }
    }

    #[Route('/image/{fileId}', name: 'image_get', methods: ['GET'])]
    public function getImage(string $fileId): Response
    {
        try {
            $file = $this->getFolder() . '/' . $fileId . '.' . $this->getFileExtension();
            if (!file_exists($file)) {
                throw new \Exception('Image not found', Response::HTTP_NOT_FOUND);
            }
            return new BinaryFileResponse($file);
        }
        catch (\Exception $e) {
            $this->log($e->getMessage());
            return new Response($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Decode image.
     *
     * @param string $encoded
     *
     * @return string
     */
    protected function decodeImage(string $encoded): string
    {
        $decoded = str_replace(' ', '+', $encoded);
        return base64_decode($decoded);
    }

    /**
     * Get folder for image.
     *
     * @return string
     */
    protected function getFolder(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/images' . $this->getUser()->getFolder();
    }

    /**
     * Get allowed file extension from allowed mime type.
     *
     * @return string
     */
    protected function getFileExtension(): string
    {
        return explode('/', $this->getParameter('allowed_mime_type'))[1];
    }

    /**
     * Write logs.
     *
     * @param string $message
     */
    protected function log(string $message)
    {
        $user = $this->getUser();
        if ($user) {
            $username = $user->getUserIdentifier();
        }
        else {
            $username = 'Anonymous';
        }
        $this->logger->info('Username: ' . $username . ' | Message: ' . $message);
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(Request $request): Response {
        $username = $request->query->get('username');
        $password = $request->query->get('password');

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user && $this->passwordHasher->isPasswordValid($user, $password)) {

            $token = bin2hex(random_bytes(60));
            $user->setApiToken($token);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->json(['token' => $token]);
        }
        return new Response('Wrong credentials', Response::HTTP_UNAUTHORIZED);
    }

}
