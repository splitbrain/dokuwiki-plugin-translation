<?php

/**
 * General tests for the translation plugin
 *
 * @group plugin_translation
 * @group plugins
 */
class basic_plugin_translation_test extends DokuWikiTest {

    protected $pluginsEnabled = array('translation');

    public static function buildTransID_testdata() {
        return array(
            array(
                'en',
                'ns:page',
                'de es',
                array(':ns:page', 'en'),
            ),
            array(
                '',
                'ns:page',
                'de es',
                array(':ns:page', 'en'),
            ),
            array(
                'de',
                'ns:page',
                'de es',
                array(':de:ns:page', 'de'),
            ),
        );
    }

    /**
     * @dataProvider buildTransID_testdata
     *
     * @param $inputLang
     * @param $inputID
     * @param $translationsOption
     * @param $expected
     */
    public function test_buildTransID($inputLang, $inputID, $translationsOption, $expected) {
        global $conf;
        $conf['plugin']['translation']['translations'] = $translationsOption;
        /** @var helper_plugin_translation $helper */
        $helper = plugin_load('helper', 'translation', true);

        $actual_result = $helper->buildTransID($inputLang, $inputID);

        $this->assertEquals($expected, $actual_result);
    }


    public static function redirectStart_testdata() {
        return array(
            array(
                'start',
                'de es',
                'de,en-US;q=0.8,en;q=0.5,fr;q=0.3',
                'de:start',
                'redirect to translated page',
            ),
            array(
                'start',
                'de es',
                'en-US,de;q=0.8,en;q=0.5,fr;q=0.3',
                array(),
                'do not redirect if basic namespace is correct lang',
            ),
            array(
                'de:start',
                'en de es',
                'en-US,en;q=0.8,fr;q=0.5',
                array(),
                'do not redirect anything other than exactly $conf[\'start\']',
            ),
        );
    }


    /**
     * @dataProvider redirectStart_testdata
     *
     * @param $input
     * @param $translationsOption
     * @param $httpAcceptHeader
     * @param $expected
     */
    public function test_redirectStart($input, $translationsOption, $httpAcceptHeader, $expected, $msg) {
        global $conf;
        $conf['plugin']['translation']['translations'] = $translationsOption;
        $conf['plugin']['translation']['redirectstart'] = 1;

        /** @var helper_plugin_translation $helper */
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
