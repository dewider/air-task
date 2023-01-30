<?

use \Bitrix\Iblock\Iblock;

\Bitrix\Main\Loader::includeModule("iblock");

define(CITY_IBLOCK_ID, 1);
define(EVENT_IBLOCK_ID, 2);
define(MEMBER_IBLOCK_ID, 3);

$eventEntity = Iblock::wakeUp(EVENT_IBLOCK_ID)->getEntityDataClass();
$cityEntity = Iblock::wakeUp(CITY_IBLOCK_ID)->getEntityDataClass();
$memberEntity = Iblock::wakeUp(MEMBER_IBLOCK_ID)->getEntityDataClass();

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$rsEvent = $eventEntity::getList([
    'select' => [
        'NAME', 'ACTIVE_FROM', 'ACTIVE_TO', 'CITY_ID_' => 'CITY_ID',
        'CITY_' => 'CITY', 'MEMBER_ID_' => 'MEMBER_ID',
    ],
    $filter => [
        '>=ACTIVE_FROM' => $request->get('date-from'),
        '<ACTIVE_TO' => $request->get('date-to'),
    ],
    'runtime' => [
        'CITY' => [
            'data_type' => $cityEntity,
            'reference' => array('=this.CITY_ID_VALUE' => 'ref.ID')
        ]
    ],
    'cache' => [
        'ttl' => 3600
    ]
])->fetchCollection();

$arResult = [];
$membersId = [];
foreach ($rsEvent as $event) {
    $id = $event->getId();
    $arResult[$id] = [
        'ID' => $id,
        'NAME' => $event->getName(),
        'CITY_ID' => $event->getCityId->getValue(),
        'CITY_NAME' => $event->getCity->getName(), //не проверял
        'ACTIVE_FROM' => $event->getActiveFrom(),
        'ACTIVE_TO' => $event->getActiveTo(),
        'MEMBERS' => []
    ];
    foreach ($event->getMamberId->getAll() as $member) {
        $memberId = $member->getValue();
        $membersId[] = $memberId;
        $arResult[$id]['MEMBERS'][$memberId] = [];
    }
}

$rsMember = $memberEntity::getList([
    'select' => [
        'NAME', 'ID'
    ],
    $filter => [
        'ID' => $membersId
    ],
    'cache' => [
        'ttl' => 3600
    ]
]);
$membersTitleFromId = [];
while ($arMember = $rsMember->fetch()) {
    $membersTitleFromId[$arMember['ID']] = $arMember['NAME'];
}

foreach ($arResult as &$event) {
    foreach ($event['MEMBERS'] as &$member) {
        $id = $member['ID'];
        $member['NAME'] = $membersTitleFromId[$id];
    }
}
unset($member);
unset($event);

// спикок событий лежит в $arResult