<?php

namespace yN\Engine\Media;

defined('YARONET') or die;

\Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

class Emoji
{
    const CUSTOM_NAME_LENGTH_MAX = 128;
    const CUSTOM_NAME_LENGTH_MIN = 1;
    const CUSTOM_NAME_PREFIX = 'emoji-';

    private static $natives = array(
        'normal' => array(
            // Standards
            'alien'			=> 'alien',
            'angry'			=> 'angry',
            'apple'			=> 'apple',
            'arme'			=> 'arme',
            'attention'		=> 'attention',
            'aveugle'		=> 'aveugle',
            'banana'		=> 'banana',
            'bandana'		=> 'bandana',
            'bang'			=> 'bang',
            'beer'			=> 'beer',
            'beret'			=> 'beret',
            'bigeyes'		=> 'bigeyes',
            'birthday'		=> 'birthday',
            'bisoo'			=> 'bisoo',
            'blabla'		=> 'blabla',
            'black'			=> 'black',
            'bobo'			=> 'bobo',
            'boing'			=> 'boing',
            'bonbon'		=> 'bonbon',
            'bouquet'		=> 'bouquet',
            'bourre'		=> 'bourre',
            'bulle'			=> 'bulle',
            'cake'			=> 'cake',
            'calin'			=> 'calin',
            'camouflage'	=> 'camouflage',
            'car'			=> 'car',
            'casque'		=> 'casque',
            'censure'		=> 'censure',
            'chante'		=> 'chante',
            'chapo'			=> 'chapo',
            'chat'			=> 'chat',
            'chausson'		=> 'chausson',
            'cheeky'		=> 'cheeky',
            'chew'			=> 'chew',
            'chinois'		=> 'chinois',
            'christmas'		=> 'christmas',
            'ciao'			=> 'ciao',
            'citrouille'	=> 'citrouille',
            'classe'		=> 'classe',
            'clover'		=> 'clover',
            'coin'			=> 'coin',
            'confus'		=> 'confus',
            'cookie'		=> 'cookie',
            'cool'			=> 'cool',
            'couic'			=> 'couic',
            'couic2'		=> 'couic2',
            'couple'		=> 'couple',
            'cow'			=> 'cow',
            'cowboy'		=> 'cowboy',
            'crash'			=> 'crash',
            'crocodile'		=> 'crocodile',
            'croque'		=> 'croque',
            'cry'			=> 'cry',
            'cubiste'		=> 'cubiste',
            'cyborg'		=> 'cyborg',
            'dehors'		=> 'dehors',
            'devil'			=> 'devil',
            'diable'		=> 'diable',
            'dingue'		=> 'dingue',
            'doc'			=> 'doc',
            'doom'			=> 'doom',
            'doughnut'		=> 'doughnut',
            'drapeaublanc'	=> 'drapeaublanc',
            'ecoute'		=> 'ecoute',
            'eeek'			=> 'eeek',
            'eek'			=> 'eek',
            'embarrassed'	=> 'embarrassed',
            'enflamme'		=> 'enflamme',
            'epee'			=> 'epee',
            'faq'			=> 'faq',
            'fatigue'		=> 'fatigue',
            'fear'			=> 'fear',
            'fesses'		=> 'fesses',
            'feu'			=> 'feu',
            'fireball'		=> 'fireball',
            'flag'			=> 'flag',
            'fleche'		=> 'fleche',
            'fondu'			=> 'fondu',
            'forbidden'		=> 'forbidden',
            'fou'			=> 'fou',
            'fou2'			=> 'fou2',
            'fou3'			=> 'fou3',
            'fouet'			=> 'fouet',
            'froid'			=> 'froid',
            'fuck'			=> 'fuck',
            'fucktricol'	=> 'fucktricol',
            'furax'			=> 'furax',
            'furieux'		=> 'furieux',
            'ghost'			=> 'ghost',
            'girl'			=> 'girl',
            'gni'			=> 'gni',
            'gol'			=> 'gol',
            'grin'			=> 'grin',
            'groupe'		=> 'groupe',
            'guitar'		=> 'guitar',
            'happy'			=> 'happy',
            'heart'			=> 'heart',
            'hehe'			=> 'hehe',
            'helico'		=> 'helico',
            'hippy'			=> 'hippy',
            'honeybee'		=> 'honeybee',
            'hotdog'		=> 'hotdog',
            'hum'			=> 'hum',
            'hum2'			=> 'hum2',
            'hypno'			=> 'hypno',
            'icecream'		=> 'icecream',
            'info'			=> 'info',
            'interdit2'		=> 'interdit2',
            'karate'		=> 'karate',
            'key'			=> 'key',
            'king'			=> 'king',
            'kiss'			=> 'kiss',
            'langue'		=> 'langue',
            'laught'		=> 'laught',
            'livre'			=> 'livre',
            'lol'			=> 'lol',
            'lolpaf'		=> 'lolpaf',
            'loupe'			=> 'loupe',
            'love'			=> 'love',
            'lune'			=> 'lune',
            'mad'			=> 'mad',
            'magic'			=> 'magic',
            'marteau'		=> 'marteau',
            'masque'		=> 'masque',
            'miam'			=> 'miam',
            'microphone'	=> 'microphone',
            'mimi'			=> 'mimi',
            'mobile'		=> 'mobile',
            'mourn'			=> 'mourn',
            'mur'			=> 'mur',
            'mushroom'		=> 'mushroom',
            'neutral'		=> 'neutral',
            'nib'			=> 'nib',
            'non'			=> 'non',
            'note'			=> 'note',
            'octopus'		=> 'octopus',
            'ooh'			=> 'ooh',
            'oui'			=> 'oui',
            'pam'			=> 'pam',
            'pencil'		=> 'pencil',
            'piano'			=> 'piano',
            'picol'			=> 'picol',
            'pizza'			=> 'pizza',
            'pluie'			=> 'pluie',
            'poisson'		=> 'poisson',
            'police'		=> 'police',
            'poultry'		=> 'poultry',
            'present'		=> 'present',
            'princess'		=> 'princess',
            'pumpkin'		=> 'pumpkin',
            'rabbit'		=> 'rabbit',
            'rage'			=> 'rage',
            'roll'			=> 'roll',
            'rotfl'			=> 'rotfl',
            'sad'			=> 'sad',
            'santa'			=> 'santa',
            'saucisse'		=> 'saucisse',
            'scotch'		=> 'scotch',
            'shhh'			=> 'shhh',
            'sick'			=> 'sick',
            'skate'			=> 'skate',
            'skull'			=> 'skull',
            'slug'			=> 'slug',
            'slurp'			=> 'slurp',
            'smile'			=> 'smile',
            'snail'			=> 'snail',
            'snowflake'		=> 'snowflake',
            'snowman'		=> 'snowman',
            'soccer'		=> 'soccer',
            'soda'			=> 'soda',
            'sorry'			=> 'sorry',
            'splat'			=> 'splat',
            'starwars'		=> 'starwars',
            'stylobille'	=> 'stylobille',
            'sun'			=> 'sun',
            'superguerrier'	=> 'superguerrier',
            'surf'			=> 'surf',
            'swirl'			=> 'swirl',
            'tasse'			=> 'tasse',
            'tilt'			=> 'tilt',
            'toilettes'		=> 'toilettes',
            'tomato'		=> 'tomato',
            'tombe'			=> 'tombe',
            'tongue'		=> 'tongue',
            'top'			=> 'top',
            'tricol'		=> 'tricol',
            'trifaq'		=> 'trifaq',
            'trifouet'		=> 'trifouet',
            'trifus'		=> 'trifus',
            'trigic'		=> 'trigic',
            'trigni'		=> 'trigni',
            'trilangue'		=> 'trilangue',
            'trilol'		=> 'trilol',
            'trilove'		=> 'trilove',
            'trinon'		=> 'trinon',
            'trioui'		=> 'trioui',
            'tripaf'		=> 'tripaf',
            'tripo'			=> 'tripo',
            'triroll'		=> 'triroll',
            'triso'			=> 'triso',
            'trisors'		=> 'trisors',
            'trisotfl'		=> 'trisotfl',
            'tritop'		=> 'tritop',
            'trivil'		=> 'trivil',
            'tromb'			=> 'tromb',
            'trophy'		=> 'trophy',
            'trumpet'		=> 'trumpet',
            'tsss'			=> 'tsss',
            'turtle'		=> 'turtle',
            'tusors'		=> 'tusors',
            'tv'			=> 'tv',
            'vador'			=> 'vador',
            'vtff'			=> 'vtff',
            'warp'			=> 'warp',
            'wc'			=> 'wc',
            'what'			=> 'what',
            'wink'			=> 'wink',
            'yel'			=> 'yel',
            'yin'			=> 'yin',
            'yoyo'			=> 'yoyo',
            'zen'			=> 'zen',
            'zzz'			=> 'zzz',

            // Aliases
            'anniv'			=> 'birthday',
            'banane'		=> 'banana',
            'biere'			=> 'beer',
            'biz'			=> 'kiss',
            'bzz'			=> 'honeybee',
            'champignon'	=> 'mushroom',
            'citrouille2'	=> 'pumpkin',
            'cle'			=> 'key',
            'coeur'			=> 'heart',
            'cornet'		=> 'icecream',
            'coupe'			=> 'trophy',
            'crayon'		=> 'pencil',
            'cuisse'		=> 'poultry',
            'donut'			=> 'doughnut',
            'drapeau'		=> 'flag',
            'fantome'		=> 'ghost',
            'fille'			=> 'girl',
            'fleurs'		=> 'bouquet',
            'flic'			=> 'police',
            'flocon'		=> 'snowflake',
            'foot'			=> 'soccer',
            'gato'			=> 'cake',
            'grrr'			=> 'angry',
            'guitare'		=> 'guitar',
            'hein'			=> 'what',
            'interdit'		=> 'forbidden',
            'kado'			=> 'present',
            'krokro'		=> 'crocodile',
            'lapin'			=> 'rabbit',
            'love2'			=> 'couple',
            'meuh'			=> 'cow',
            'micro'			=> 'microphone',
            'ouin'			=> 'cry',
            'peur'			=> 'fear',
            'pleure'		=> 'mourn',
            'pomme'			=> 'apple',
            'poulpe'		=> 'octopus',
            'reine'			=> 'princess',
            'sapin'			=> 'christmas',
            'soleil'		=> 'sun',
            'stylo'			=> 'nib',
            'sygus'			=> 'laught',
            'tomate'		=> 'tomato',
            'tompette'		=> 'trumpet',
            'tortue'		=> 'turtle',
            'trefle'		=> 'clover'
        ),
        'small' => array(
            // Standards
            'fc-00'		=> 'fc-00',
            'fc-01'		=> 'fc-01',
            'fc-02'		=> 'fc-02',
            'fc-03'		=> 'fc-03',
            'fc-04'		=> 'fc-04',
            'fc-05'		=> 'fc-05',
            'fc-06'		=> 'fc-06',
            'fc-07'		=> 'fc-07',
            'fc-08'		=> 'fc-08',
            'fc-09'		=> 'fc-09',
            'fc-10'		=> 'fc-10',
            'fc-11'		=> 'fc-11',
            'fc-12'		=> 'fc-12',
            'fc-bisoo'	=> 'fc-bisoo',
            'fc-boing'	=> 'fc-boing',
            'fc-bud'	=> 'fc-bud',
            'fc-calin'	=> 'fc-calin',
            'fc-colere'	=> 'fc-colere',
            'fc-ko'		=> 'fc-ko',
            'fc-lol'	=> 'fc-lol',
            'fc-triso'	=> 'fc-triso',
            'fc-vero'	=> 'fc-vero',

            // Aliases
            'bud'		=> 'fc-bud',
            'boing'		=> 'fc-boing',
            'bisoo'		=> 'fc-bisoo',
            'ko'		=> 'fc-ko',
            'vero'		=> 'fc-vero',
            'lol'		=> 'fc-lol',
            'calin'		=> 'fc-calin',
            'colere'	=> 'fc-colere',
            'triso'		=> 'fc-triso'
        )
    );

    public static function check_custom($name)
    {
        \Glay\using('yN\\Engine\\Media\\Binary', './engine/media/binary.php');

        $length = mb_strlen($name);

        return $length >= self::CUSTOM_NAME_LENGTH_MIN && $length <= self::CUSTOM_NAME_LENGTH_MAX && Binary::check(self::CUSTOM_NAME_PREFIX . $name);
    }

    public static function check_native(&$name, $category)
    {
        if (!isset(self::$natives[$category]) || !isset(self::$natives[$category][$name])) {
            return false;
        }

        $name = self::$natives[$category][$name];

        return true;
    }

    public static function get_custom_tag($name)
    {
        return '##' . $name . '##';
    }

    public static function get_native_tag($name)
    {
        return '#' . $name . '#';
    }

    public static function list_custom($router, $search)
    {
        \Glay\using('yN\\Engine\\Media\\Binary', './engine/media/binary.php');

        $emojis = array();
        $length = mb_strlen(self::CUSTOM_NAME_PREFIX);

        foreach (Binary::browse(self::CUSTOM_NAME_PREFIX . Binary::escape($search) . '*') as $name) {
            $emojis[self::get_custom_tag(mb_substr($name, $length))] = self::url_custom($router, mb_substr($name, $length));
        }

        return $emojis;
    }

    public static function list_native($category)
    {
        $emojis = array();

        if (isset(self::$natives[$category])) {
            foreach (self::$natives[$category] as $name) {
                $emojis[self::get_native_tag($name)] = self::url_native($name);
            }
        }

        return $emojis;
    }

    public static function put_custom($name, $data)
    {
        \Glay\using('yN\\Engine\\Media\\Binary', './engine/media/binary.php');

        return Binary::put(self::CUSTOM_NAME_PREFIX . $name, $data);
    }

    public static function url_custom($router, $name)
    {
        return $router->url('media.image.render', array('name' => self::CUSTOM_NAME_PREFIX . $name, '_template' => null));
    }

    public static function url_native($name)
    {
        return \yN\Engine\Network\URL::to_static() . 'image/emoji/' . $name . '.gif';
    }
}
