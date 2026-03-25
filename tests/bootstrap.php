<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'test') {
    $kernel = new App\Kernel('test', true);
    $kernel->boot();
    $em = $kernel->getContainer()->get('doctrine')->getManager();
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
    $metadata = $em->getMetadataFactory()->getAllMetadata();
    if (!empty($metadata)) {
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
    $kernel->shutdown();
}
