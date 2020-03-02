<?php
namespace AppBundle\Tests\Controller\Api;

use AppBundle\Test\ApiTestCase;

class ProgrammerControllerTest extends ApiTestCase // This class is a PHPunit test class, each method tests a key part of the API
{
    protected function setUp() // Creating another setUp method, but not to override the parent method. We are using it to add something that will only be used in this test suite
    {
        parent::setUp(); // Calling to the parent setUp method so that we still get all the code inside it here

        $this->createUser('weaverryan'); // Adding the code we want to specifically add to the setUp for this test suite, which is creating the user with a specific username of weaverryan
    }

    public function testPOST() // Testing the POST endpoint. (Creating a new programmer resource)
    {
        $data = array( // Assigning the $programmer resources data to this $data array
            'nickname' => 'ObjectOrienter',
            'avatarNumber' => 5,
            'tagLine' => 'a test dev!'
        );

        // Creating the programmer resource by making a post require to our API POST endpoint and setting the 'body' key by passing it the $data array encoded with JSON
        $response = $this->client->post('/api/programmers', [
            'body' => json_encode($data)
        ]);

        $this->assertEquals(201, $response->getStatusCode()); // Asserting that the response code that comes back from the response is equal to 201
        $this->assertTrue($response->hasHeader('Location')); // Asserting that the response has a header
        $this->assertStringEndsWith('/api/programmers/ObjectOrienter', $response->getHeader('Location')); // Asserting that the URI ends with the programmer resources nickname that we created
        $finishedData = json_decode($response->getBody(true), true); // Decoding the response body into an array
        $this->assertArrayHasKey('nickname', $finishedData); // Asserting that the array we get back from the response has a 'nickname key'
        $this->assertEquals('ObjectOrienter', $finishedData['nickname']); // Asserting that array key 'nickname' is equal to the nickname we set for the programmer resource
    }

    public function testGETProgrammer() // Testing the GET programmer endpoint (getting a single programmer resource)
    {
        $this->createProgrammer(array( // Using the createProgrammer method and passing it an array of keys and values which we want this programmer resource to have
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $response = $this->client->get('/api/programmers/UnitTester'); // Creating the response by using the client to send a GET request to the specified URL to get the specific programmer resource that has the nickname "UnitTester"
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array( // Asserting that the 4 properties of a programmer resource exists in the HTTP response
            'nickname',
            'avatarNumber',
            'powerLevel',
            'tagLine'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'UnitTester'); // Asserting that the HTTP response has a 'nickname' property which is set to 'UnitTester'
                                                                                                // Using "asserter()" method instead of "assert()" because it is better at reading HTTP responses and looking for properties inside them
    }

    public function testGETProgrammersCollection() // Testing the GET programmer endpoint (getting an collection of programmer resources)
    {
        $this->createProgrammer(array(
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));
        $this->createProgrammer(array(
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
        ));

        $response = $this->client->get('/api/programmers');
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyIsArray($response, 'programmers'); // Asserting that the response has a property 'programmers' that is an array
        $this->asserter()->assertResponsePropertyCount($response, 'programmers', 2); // Asserting that there are two things on this array
        $this->asserter()->assertResponsePropertyEquals($response, 'programmers[1].nickname', 'CowboyCoder'); // Asserting that in the response data, the second item in the array has a nickname key that has a value of CowboyCoder
    }

    public function testPUTProgrammer() // Testing the PUT endpoint (Updating a programmer resource)
    {
        $this->createProgrammer(array( // Creating the programmer resource in the database
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine' => 'foo',
        ));

        $data = array( // This is the data that we want to use to update the programmer resource
            'nickname' => 'CowgirlCoder',
            'avatarNumber' => 2,
            'tagLine' => 'foo',
        );
        $response = $this->client->put('/api/programmers/CowboyCoder', [ // Sending a put request to the PUT endpoint with the body data as the new the array which holds the updated programmers details which will override the old ones in the database
            'body' => json_encode($data)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'avatarNumber', 2); // Asserting that the avatarNumber has changed it's value from 5 to 2
        // the nickname is immutable on edit
        $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'CowboyCoder');
    }

    public function testPATCHProgrammer() // Testing a PATCH request to the same endpoint as PUT
    {
        $this->createProgrammer(array( // Creating the programmer resource
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine' => 'foo',
        ));

        $data = array( // Since a PATCH request is better for simply updating one property of a resource, we are only updating the tagLine property
            'tagLine' => 'bar',
        );
        $response = $this->client->patch('/api/programmers/CowboyCoder', [ // Making the PATCH request to the following endpoint
            'body' => json_encode($data) // encoding the body data with the $data array that contains the property we want to update and the value we want to update it to
        ]);
        $this->assertEquals(200, $response->getStatusCode()); // Asserting that the response status code is 200
        $this->asserter()->assertResponsePropertyEquals($response, 'avatarNumber', 5); // Asserting that the property avatarNumber in the body data has a value of 5
        $this->asserter()->assertResponsePropertyEquals($response, 'tagLine', 'bar'); // Asserting that the property tagLine in the body data has a value of bar
    }

    public function testDELETEProgrammer() // Testing the DELETE endpoint (Deleting a programmer resource)
    {
        $this->createProgrammer(array( // Creating a programmer
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $response = $this->client->delete('/api/programmers/UnitTester'); // Sending a delete request to the following endpoint
        $this->assertEquals(204, $response->getStatusCode()); // Asserting that the the response status code is equal to 204
    }
}
