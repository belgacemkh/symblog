<?php

namespace App\DataFixtures;

use App\Entity\Keywords;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class KeywordsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $keyword = new Keywords();
        $keyword->setName('test');
        $keyword->setSlug('test');

        $manager->persist($keyword);

        $manager->flush();
    }
}