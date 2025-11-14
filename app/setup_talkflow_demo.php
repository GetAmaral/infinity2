<?php

require_once 'vendor/autoload.php';

use App\Entity\Agent;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Organization;
use App\Entity\Talk;
use App\Entity\TalkMessage;
use App\Entity\TreeFlow;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== Setting up TalkFlow Demo ===\n\n";

// Get organization
$org = $em->getRepository(Organization::class)->findOneBy(['slug' => 'test-org-1']);
if (!$org) {
    echo "ERROR: Organization not found\n";
    exit(1);
}
echo "✓ Organization: {$org->getName()}\n";

// Get agent
$agent = $em->getRepository(Agent::class)->findOneBy([
    'organization' => $org,
    'name' => 'AI SDR Agent - Sarah'
]);
if (!$agent) {
    echo "ERROR: Agent not found\n";
    exit(1);
}
echo "✓ Agent: {$agent->getName()}\n";

// Get TreeFlow
$treeFlow = $em->getRepository(TreeFlow::class)->findOneBy(['slug' => 'test_treeflow_for_api_23']);
if (!$treeFlow) {
    echo "ERROR: TreeFlow not found\n";
    exit(1);
}
echo "✓ TreeFlow: {$treeFlow->getName()}\n";

// Create Company (simplified)
$company = new Company();
$company->setOrganization($org);
$company->setName('Demo Company Inc');
$em->persist($company);
$em->flush();
echo "✓ Created Company: {$company->getName()}\n";

// Create Contact
$contact = new Contact();
$contact->setOrganization($org);
$contact->setCompany($company);
$contact->setName('John Doe');
$contact->setFirstName('John');
$contact->setLastName('Doe');
$contact->setEmail('john.doe@democompany.com');
$contact->setPhone('+1-555-0123');
$em->persist($contact);
$em->flush();
echo "✓ Created Contact: {$contact->getName()}\n";

// Create Talk
$talk = new Talk();
$talk->setOrganization($org);
$talk->setContact($contact);
$talk->addAgent($agent);
$talk->setSubject('Demo - AI SDR Conversation');
$talk->setStatus(1); // Active
$talk->setDirection('inbound');
$talk->setMessageCount(0);
$talk->setDateFirstMessage(new \DateTimeImmutable());
$talk->setDateLastMessage(new \DateTimeImmutable());
$em->persist($talk);
$em->flush();
echo "✓ Created Talk ID: {$talk->getId()->toRfc4122()}\n";

// Initialize TalkFlow
$talkFlowService = $container->get('App\Service\TalkFlow\TalkFlowService');
$talkFlowService->initializeTalkFlow($talk, $treeFlow);
echo "✓ Initialized TalkFlow\n";

// Create first inbound message from contact
$message = new TalkMessage();
$message->setTalk($talk);
$message->setOrganization($org);
$message->setFromContact($contact);
$message->setBody("Hi, I'm interested in learning more about your services. Can you help me?");
$message->setDirection('inbound');
$message->setMessageType('text');
$message->setSentAt(new \DateTimeImmutable());
$em->persist($message);

// Update talk message count
$talk->setMessageCount($talk->getMessageCount() + 1);
$talk->setDateLastMessage(new \DateTimeImmutable());
$em->flush();

echo "✓ Created inbound message ID: {$message->getId()->toRfc4122()}\n";

echo "\n=== Setup Complete! ===\n\n";
echo "Talk ID: {$talk->getId()->toRfc4122()}\n";
echo "Message ID: {$message->getId()->toRfc4122()}\n";
echo "TreeFlow: {$treeFlow->getSlug()}\n";
echo "Agent: {$agent->getName()}\n\n";
echo "The TalkMessageSubscriber will automatically trigger async processing.\n";
echo "Start the messenger worker to see it in action:\n";
echo "  docker-compose exec app php bin/console messenger:consume async -vv\n\n";

// Output Talk URL
echo "View Talk in browser:\n";
echo "  https://test-org-1.localhost/talk/show/{$talk->getId()->toRfc4122()}\n\n";
