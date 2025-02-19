<?php

namespace App\Tests\Controller\Admin;

use App\Tests\GetUser;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    use GetUser;

    #[TestWith(['admin'])]
    #[TestWith(['visitor'])]
    public function testAdminPermissions(string $username): void
    {
        $client = self::createClient();

        $client->loginUser($this->getUser($username));
        $client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        $links = $client->getCrawler()->filter('#main-menu a')->each(fn ($node) => $node->text());
        $expectedLinks = [
            'Tableau de bord',
            'Évènements',
            'Calendrier',
            'Lieux d\'accueil',
            'Étages',
            'Salles',
            'Stands',
            'Activités',
            'Programmation',
            'Créneaux horaires',
        ];
        if ($username === 'admin') {
            $expectedLinks[] = 'Utilisateurs/utilisatrices';
        }
        self::assertSame($expectedLinks, $links);
    }
}
