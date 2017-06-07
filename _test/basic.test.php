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

}
