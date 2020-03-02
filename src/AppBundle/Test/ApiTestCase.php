<?php

namespace AppBundle\Test;

use AppBundle\Entity\Programmer;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Message\AbstractMessage;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\History;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ApiTestCase extends KernelTestCase // This is the base class for all our API tests. Every test controller will extend this class because it has a lot of the code that they will need
{
    private static $staticClient;

    /**
     * @var History
     */
    private static $history;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    private $responseAsserter;

    public static function setUpBeforeClass() // Making sure that the HTTP client is created once per test suite
    {
        $baseUrl = getenv('TEST_BASE_URL'); // Creating a base URL by getting an environment variable called 'TEST_BASE_URL'
        self::$staticClient = new Client([
            'base_url' => $baseUrl,
            'defaults' => [
                'exceptions' => false
            ]
        ]);
        self::$history = new History();
        self::$staticClient->getEmitter()
            ->attach(self::$history);

        // guaranteeing that /app_test.php is prefixed to all URLs
        self::$staticClient->getEmitter()
            ->on('before', function(BeforeEvent $event) {
                $path = $event->getRequest()->getPath();
                if (strpos($path, '/api') === 0) {
                    $event->getRequest()->setPath('/app_test.php'.$path);
                }
            });

        self::bootKernel(); // booting kernel so that the service container is available
    }

    protected function setUp() // This method puts the client onto a non-static property once it has been created
    {
        $this->client = self::$staticClient;

        $this->purgeDatabase(); // Purges the database before running any tests to ensure that the database is in a predictable state
    }

    /**
     * Clean up Kernel usage in this test.
     */
    protected function tearDown()
    {
        // purposefully not calling parent class, which shuts down the kernel
    }

    protected function onNotSuccessfulTest(Exception $e) // Whenever a PHPUNit test fails, this method will be called to give more information on why the test failed
    {
        if (self::$history && $lastResponse = self::$history->getLastResponse()) {
            $this->printDebug('');
            $this->printDebug('<error>Failure!</error> when making the following request:');
            $this->printLastRequestUrl();
            $this->printDebug('');

            $this->debugResponse($lastResponse);
        }

        throw $e;
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->getService('doctrine')->getManager()); // Using the ORMPurger class and passing it the entity manager so that it can purge the database of all test data
        $purger->purge();
    }

    protected function getService($id) // The point of this method is to let our test classes fetch service classes from the container and use them
    {
        return self::$kernel->getContainer() // when this method is called, it returns the service class container
            ->get($id);
    }

    protected function printLastRequestUrl()
    {
        $lastRequest = self::$history->getLastRequest();

        if ($lastRequest) {
            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', $lastRequest->getMethod(), $lastRequest->getUrl()));
        } else {
            $this->printDebug('No request was made.');
        }
    }

    protected function debugResponse(ResponseInterface $response)
    {
        $this->printDebug(AbstractMessage::getStartLineAndHeaders($response));
        $body = (string) $response->getBody();

        $contentType = $response->getHeader('Content-Type');
        if ($contentType == 'application/json' || strpos($contentType, '+json') !== false) {
            $data = json_decode($body);
            if ($data === null) {
                // invalid JSON!
                $this->printDebug($body);
            } else {
                // valid JSON, print it pretty
                $this->printDebug(json_encode($data, JSON_PRETTY_PRINT));
            }
        } else {
            // the response is HTML - see if we should print all of it or some of it
            $isValidHtml = strpos($body, '</body>') !== false;

            if ($isValidHtml) {
                $this->printDebug('');
                $crawler = new Crawler($body);

                // very specific to Symfony's error page
                $isError = $crawler->filter('#traces-0')->count() > 0
                    || strpos($body, 'looks like something went wrong') !== false;
                if ($isError) {
                    $this->printDebug('There was an Error!!!!');
                    $this->printDebug('');
                } else {
                    $this->printDebug('HTML Summary (h1 and h2):');
                }

                // finds the h1 and h2 tags and prints them only
                foreach ($crawler->filter('h1, h2')->extract(array('_text')) as $header) {
                    // avoid these meaningless headers
                    if (strpos($header, 'Stack Trace') !== false) {
                        continue;
                    }
                    if (strpos($header, 'Logs') !== false) {
                        continue;
                    }

                    // remove line breaks so the message looks nice
                    $header = str_replace("\n", ' ', trim($header));
                    // trim any excess whitespace "foo   bar" => "foo bar"
                    $header = preg_replace('/(\s)+/', ' ', $header);

                    if ($isError) {
                        $this->printErrorBlock($header);
                    } else {
                        $this->printDebug($header);
                    }
                }

                /*
                 * When using the test environment, the profiler is not active
                 * for performance. To help debug, turn it on temporarily in
                 * the config_test.yml file (framework.profiler.collect)
                 */
                $profilerUrl = $response->getHeader('X-Debug-Token-Link');
                if ($profilerUrl) {
                    $fullProfilerUrl = $response->getHeader('Host').$profilerUrl;
                    $this->printDebug('');
                    $this->printDebug(sprintf(
                        'Profiler URL: <comment>%s</comment>',
                        $fullProfilerUrl
                    ));
                }

                // an extra line for spacing
                $this->printDebug('');
            } else {
                $this->printDebug($body);
            }
        }
    }

    /**
     * Print a message out - useful for debugging
     *
     * @param $string
     */
    protected function printDebug($string)
    {
        if ($this->output === null) {
            $this->output = new ConsoleOutput();
        }

        $this->output->writeln($string);
    }

    /**
     * Print a debugging message out in a big red block
     *
     * @param $string
     */
    protected function printErrorBlock($string)
    {
        if ($this->formatterHelper === null) {
            $this->formatterHelper = new FormatterHelper();
        }
        $output = $this->formatterHelper->formatBlock($string, 'bg=red;fg=white', true);

        $this->printDebug($output);
    }

    protected function createUser($username, $plainPassword = 'foo') // Creates a user with the passed $username and $plainPassword. The plain password is optional and doesn't need to be passed, it if it not passed then it will be set to foo
    {
        $user = new User(); // Creating a user object
        $user->setUsername($username); // giving it the required data that was passed to this method
        $user->setEmail($username.'@foo.com'); // setting their email to whatever their username is and adding @foo.com to the end
        $password = $this->getService('security.password_encoder') // using the service container to get the encodePassword method
            ->encodePassword($user, $plainPassword);
        $user->setPassword($password);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createProgrammer(array $data) // When this method is called, it takes the data that is passed to it and creates a programmer resource.
    {
        $data = array_merge(array( // It takes the $data it was passed, which should be an array, and merges it with another array, which just sets default values to the other keys that were not used
            'powerLevel' => rand(0, 10),
            'user' => $this->getEntityManager() // querying for a user since there is a database relationship
                ->getRepository('AppBundle:User')
                ->findAny()
        ), $data);

        $accessor = PropertyAccess::createPropertyAccessor();
        $programmer = new Programmer();
        foreach ($data as $key => $value) {
            $accessor->setValue($programmer, $key, $value);
        }

        $this->getEntityManager()->persist($programmer);
        $this->getEntityManager()->flush();

        return $programmer;
    }

    /**
     * @return ResponseAsserter
     */
    protected function asserter()
    {
        if ($this->responseAsserter === null) {
            $this->responseAsserter = new ResponseAsserter();
        }

        return $this->responseAsserter;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager() // Creating a method that will simply be called whenever we want to use the entity manager
    {
        return $this->getService('doctrine.orm.entity_manager'); // returns the entity mananger
    }
}
