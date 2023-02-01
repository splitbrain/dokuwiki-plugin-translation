<?php

namespace dokuwiki\plugin\translation\test;

use dokuwiki\Extension\EventHandler;
use DokuWikiTest;
use TestRequest;

/**
 * General tests for the translation plugin
 *
 * @group plugin_translation
 * @group plugins
 */
class BasicTest extends DokuWikiTest
{

    protected $pluginsEnabled = ['translation'];

    /**
     * Test provider
     * @return array[]
     * @see testBuildTransID
     */
    public static function provideBuildTransID()
    {
        return [
            [
                'en',
                'ns:page',
                'de es',
                [':ns:page', 'en'],
            ],
            [
                '',
                'ns:page',
                'de es',
                [':ns:page', 'en'],
            ],
            [
                'de',
                'ns:page',
                'de es',
                [':de:ns:page', 'de'],
            ],
        ];
    }

    /**
     * @dataProvider provideBuildTransID
     *
     * @param $inputLang
     * @param $inputID
     * @param $translationsOption
     * @param $expected
     */
    public function testBuildTransID($inputLang, $inputID, $translationsOption, $expected)
    {
        global $conf;
        $conf['plugin']['translation']['translations'] = $translationsOption;
        /** @var \helper_plugin_translation $helper */
        $helper = plugin_load('helper', 'translation', true);

        $actual_result = $helper->buildTransID($inputLang, $inputID);

        $this->assertEquals($expected, $actual_result);
    }

    /**
     * Test provider
     * @return array[]
     * @see testRedirectStart
     */
    public static function provideRedirectStart()
    {
        return [
            [
                'start',
                'de es',
                'de,en-US;q=0.8,en;q=0.5,fr;q=0.3',
                'de:start',
                'redirect to translated page',
            ],
            [
                'start',
                'de es',
                'en-US,de;q=0.8,en;q=0.5,fr;q=0.3',
                [],
                'do not redirect if basic namespace is correct lang',
            ],
            [
                'de:start',
                'en de es',
                'en-US,en;q=0.8,fr;q=0.5',
                [],
                'do not redirect anything other than exactly $conf[\'start\']',
            ],
        ];
    }

    /**
     * @dataProvider provideRedirectStart
     *
     * @param $input
     * @param $translationsOption
     * @param $httpAcceptHeader
     * @param $expected
     */
    public function testRedirectStart($input, $translationsOption, $httpAcceptHeader, $expected, $msg)
    {
        global $conf;
        $conf['plugin']['translation']['translations'] = $translationsOption;
        $conf['plugin']['translation']['redirectstart'] = 1;

        // reset event handler (this should be done by the TestRequest, but it doesn't)
        global $EVENT_HANDLER;
        $EVENT_HANDLER = new EventHandler();

        /** @var \helper_plugin_translation $helper */
        $helper = plugin_load('helper', 'translation');
        $helper->loadTranslationNamespaces();

        $request = new TestRequest();
        $request->setServer('HTTP_ACCEPT_LANGUAGE', $httpAcceptHeader);

        $response = $request->get(array('id' => $input));
        $actual = $response->getHeader('Location');

        if (is_string($actual)) {
            list(, $actual) = explode('doku.php?id=', $actual);
        }

        $this->assertEquals($expected, $actual, $msg);
    }

}
