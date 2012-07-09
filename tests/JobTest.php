<?php
/**
 * Copyright 2012 Splunk, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"): you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once 'SplunkTest.php';

class JobTest extends SplunkTest
{
    public function testGetTimeout()
    {
        list($service, $http) = $this->loginToMockService();
        
        // Get job
        $http_response = (object) array(
            'status' => 204,
            'reason' => 'No Content',
            'headers' => array(),
            'body' => '');
        $http->expects($this->atLeastOnce())
             ->method('get')
             ->will($this->returnValue($http_response));
        $job = $service->getJobs()->getReference('A_JOB');
        
        // Try to touch job when server refuses to return it
        try
        {
            $this->touch($job);
            $this->assertTrue(FALSE, 'Expected Splunk_HttpException to be thrown.');
        }
        catch (Splunk_HttpException $e)
        {
            $this->assertEquals(204, $e->getResponse()->status);
        }
    }
    
    public function testMakeReady()
    {
        $maxTries = 7;
        $this->assertTrue(
            $maxTries != Splunk_Job::DEFAULT_FETCH_MAX_TRIES,
            'This test is only valid for a non-default number of fetch attempts.');
        
        list($service, $http) = $this->loginToMockService();
        
        $http_response = (object) array(
            'status' => 204,
            'reason' => 'No Content',
            'headers' => array(),
            'body' => '');
        $http->expects($this->exactly($maxTries))
             ->method('get')
             ->will($this->returnValue($http_response));
        $job = $service->getJobs()->getReference('A_JOB');
        
        $this->assertFalse($job->isReady());
        try
        {
            $job->makeReady(/*maxTries=*/$maxTries, /*delayPerRetry=*/0.1);
            $this->assertTrue(FALSE, 'Expected Splunk_HttpException to be thrown.');
        }
        catch (Splunk_HttpException $e)
        {
            $this->assertEquals(204, $e->getResponse()->status);
        }
    }
    
    public function testMakeReadyReturnsSelf()
    {
        list($service, $http) = $this->loginToMockService();
        
        $http_response = (object) array(
            'status' => 200,
            'reason' => 'OK',
            'headers' => array(),
            'body' => '
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:s="http://dev.splunk.com/ns/rest" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">
  <content type="text/xml">
  </content>
</entry>
');
        $http->expects($this->once())
             ->method('get')
             ->will($this->returnValue($http_response));
        $job = $service->getJobs()->getReference('A_JOB');
        
        $this->assertEquals($job, $job->makeReady());
    }
}