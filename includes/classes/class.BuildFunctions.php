<?php

/**
 *  2Moons
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package 2Moons
 * @author Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence MIT
 * @version 1.8.0
 * @link https://github.com/jkroepke/2Moons
 */

class BuildFunctions
{

    static $bonusList	= array(
        'Attack',
        'Defensive',
        'Shield',
        'BuildTime',
        'ResearchTime',
        'ShipTime',
        'DefensiveTime',
        'Resource',
        'Energy',
        'ResourceStorage',
        'ShipStorage',
        'FlyTime',
        'FleetSlots',
        'Planets',
        'SpyPower',
        'Expedition',
        'GateCoolTime',
        'MoreFound',
    );

    public static function getBonusList()
    {
        return self::$bonusList;
    }

    public static function displayedBuildingQueueLevel(int $storedLevel, string $buildMode): int
    {
        // The queue stores the price level (current + 1). A demolish of level 10 is stored as 11.
        if ($buildMode === 'destroy') {
            return $storedLevel - 1;
        }

        return $storedLevel;
    }

    public static function buildingLevelAfterQueueEntry(int $storedLevel, string $buildMode): int
    {
        if ($buildMode === 'destroy') {
            return $storedLevel - 2;
        }

        return $storedLevel;
    }

    public static function getRestPrice($USER, $PLANET, $Element, $elementPrice = NULL)
    {
        global $resource;

        if(!isset($elementPrice)) {
            $elementPrice	= self::getElementPrice($USER, $PLANET, $Element);
        }

        $overflow	= array();

        foreach ($elementPrice as $resType => $resPrice) {
            $available			= isset($PLANET[$resource[$resType]]) ? $PLANET[$resource[$resType]] : $USER[$resource[$resType]];
            $overflow[$resType] = max($resPrice - floor($available), 0);
        }

        return $overflow;
    }

    public static function getElementPrice($USER, $PLANET, $Element, $forDestroy = false, $forLevel = NULL) {
        global $pricelist, $resource, $reslist;

        if (in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense']) || in_array($Element, $reslist['missile'])) {
            $elementLevel = $forLevel;
        } elseif (isset($forLevel)) {
            $elementLevel = $forLevel -1;
        } elseif (isset($PLANET[$resource[$Element]])) {
            $elementLevel = $PLANET[$resource[$Element]];
        } elseif (isset($USER[$resource[$Element]])) {
            $elementLevel = $USER[$resource[$Element]];
        } else {
            return array();
        }

        $price	= array();
        foreach ($reslist['ressources'] as $resType)
        {
            if (!isset($pricelist[$Element]['cost'][$resType])) {
                continue;
            }
            $ressourceAmount	= $pricelist[$Element]['cost'][$resType];

            if ($ressourceAmount == 0) {
                continue;
            }

            $price[$resType]	= $ressourceAmount;
            if(isset($pricelist[$Element]['factor']) && $pricelist[$Element]['factor'] != 0 && $pricelist[$Element]['factor'] != 1) {
                $price[$resType]	*= pow($pricelist[$Element]['factor'], $elementLevel);
            }

            if($forLevel && (in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense']) || in_array($Element, $reslist['missile']))) {
//                $price[$resType]	-= (($price[$resType] / 100) * (4 * $USER['escalation']));
                $price[$resType]	*= $elementLevel;
            }
			
            if(in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense']) || in_array($Element, $reslist['missile'])) {
				$priceReduction	= min(30, (6 * $USER['class_ability_engineer']));
//				$priceReduction	= min(50, (5 * $USER['profitincrease']));
//				$priceReduction	= min(50, (10 * (1 + floor($USER['profitincrease']/4)));
//				$priceReduction	= priceReduction * min(1, $USER['profitincrease']);
                $price[$resType]	-= (($price[$resType] / 100) * $priceReduction);
            }
            if(in_array($Element, $reslist['build'])) {
				$priceReduction	= min(40, (8 * $USER['class_ability_negociator']));
                $price[$resType]	-= (($price[$resType] / 100) * $priceReduction);
            }
            if(in_array($Element, $reslist['tech'])) {
				$priceReduction	= min(40, (8 * $USER['class_ability_researcher']));
                $price[$resType]	-= (($price[$resType] / 100) * $priceReduction);
            }

            if($forDestroy == true) {
                $price[$resType]	= $price[$resType]/2;
            }
        }

        return $price;
    }

    public static function isTechnologieAccessible($USER, $PLANET, $Element)
    {
		
        global $requeriments, $resource, $locks;
        if(isset($locks[$Element]))
		{
			foreach($locks[$Element] as $lockElement => $EleLevel)
			{
				if (
					(isset($USER[$resource[$lockElement]]) && $USER[$resource[$lockElement]] > 0) ||
					(isset($PLANET[$resource[$lockElement]]) && $PLANET[$resource[$lockElement]] > 0)
				) {
					return false;
				}
			}
		}
        if(!isset($requeriments[$Element]))
            return true;

        foreach($requeriments[$Element] as $ReqElement => $EleLevel)
        {
            if (
                (isset($USER[$resource[$ReqElement]]) && $USER[$resource[$ReqElement]] < $EleLevel) ||
                (isset($PLANET[$resource[$ReqElement]]) && $PLANET[$resource[$ReqElement]] < $EleLevel)
            ) {
                return false;
            }
        }
        return true;
    }

    public static function getBuildingTime($USER, $PLANET, $Element, $elementPrice = NULL, $forDestroy = false, $forLevel = NULL)
    {
        global $resource, $reslist, $requeriments;

        $config	= Config::get($USER['universe']);

        $time   = 0;

        if(!isset($elementPrice)) {
            $elementPrice	= self::getElementPrice($USER, $PLANET, $Element, $forDestroy, $forLevel);
        }

        $elementCost	= 0;

        if(isset($elementPrice[901])) {
            $elementCost	+= $elementPrice[901];
        }

        if(isset($elementPrice[902])) {
            $elementCost	+= $elementPrice[902];
        }

        if	   (in_array($Element, $reslist['build'])) {
            $time	= $elementCost / ($config->game_speed * (1 + $PLANET[$resource[14]])) * pow(0.5, $PLANET[$resource[15]]) * (1 + $USER['factor']['BuildTime']);
        } elseif (in_array($Element, $reslist['fleet'])) {
            $time	= $elementCost / ($config->game_speed * (1 + $PLANET[$resource[21]])) * pow(0.5, $PLANET[$resource[15]]) * (1 + $USER['factor']['ShipTime']);
        } elseif (in_array($Element, $reslist['defense'])) {
            $time	= $elementCost / ($config->game_speed * (1 + $PLANET[$resource[21]])) * pow(0.5, $PLANET[$resource[15]]) * (1 + $USER['factor']['DefensiveTime']);
        } elseif (in_array($Element, $reslist['tech'])) {
            if(is_numeric($PLANET[$resource[31].'_inter']))
            {
                $Level	= $PLANET[$resource[31]];
            } else {
                $Level = 0;
                foreach($PLANET[$resource[31].'_inter'] as $Levels)
                {
                    if(!isset($requeriments[$Element][31]) || $Levels >= $requeriments[$Element][31])
                        $Level += $Levels;
                }
            }

            $time	= $elementCost / (1000 * (1 + $Level)) / ($config->game_speed / 2500) * pow(1 - $config->factor_university / 100, $PLANET[$resource[6]]) * (1 + $USER['factor']['ResearchTime']);
        }

        if($forDestroy) {
            $time	= floor($time * 1300);
        } else {
            $time	= floor($time * 3600);
        }

        // On retourne le temps reel de construction dans le cas de construction d'un vaisseau car le temps minimal sera pris en compte plus tard via le stack des vaisseaux
        if (in_array($Element, $reslist['fleet'])) {
            $db				= Database::get();

			$sql			= 'SELECT * FROM %%VARS%% WHERE elementID = :elementID;';
			$class		= $db->selectSingle($sql, array(
			':elementID'	=> $Element
			));
			switch ($class['shipclass']) {
			case 1:
				return max($time, $config->min_build_time - (($config->min_build_time/17) * min(5, $USER['class_ability_engineer'])));
				break;
			case 2:
				return max($time, $config->min_build_time - (($config->min_build_time/17) * min(5, $USER['class_ability_engineer'])));
				break;
			case 3:
				return max($time, $config->min_build_time - (($config->min_build_time/17) * min(5, $USER['class_ability_engineer'])));
				break;
			case 4:
				return max($time, ($config->min_build_time * 2) - (($config->min_build_time * 10) / 17) * min(5, $USER['class_ability_engineer']));
				break;
			case 5:
				return max($time, ($config->min_build_time * 10) - (($config->min_build_time * 60) / 17) * min(5, $USER['class_ability_engineer']));
				break;
			case 6:
				return max($time, ($config->min_build_time * 3600) - (($config->min_build_time * 21600) / 17) * min(5, $USER['class_ability_engineer']));
				break;
			case 7:
				return $time;
				break;
			default:
				return max($time, $config->min_build_time);
				break;
			}
        } else {
            return max($time, $config->min_build_time);
        }
    }

    public static function isElementBuyable($USER, $PLANET, $Element, $elementPrice = NULL, $forDestroy = false, $forLevel = NULL)
    {
        $rest	= self::getRestPrice($USER, $PLANET, $Element, $elementPrice, $forDestroy, $forLevel);
        return count(array_filter($rest)) === 0;
    }

    public static function getMaxConstructibleElements($USER, $PLANET, $Element, $elementPrice = NULL)
    {
        global $resource, $reslist;

        if(!isset($elementPrice)) {
            $elementPrice	= self::getElementPrice($USER, $PLANET, $Element);
        }

        $maxElement	= array();

        foreach($elementPrice as $resourceID => $price)
        {
            if(isset($PLANET[$resource[$resourceID]]))
            {
                if($price > 0)
				{
					$maxElement[]	= floor($PLANET[$resource[$resourceID]] / $price);
				}
				else
				{
					$maxElement[]	= 1000000;
				}
            }
            elseif(isset($USER[$resource[$resourceID]]))
            {
                if($price > 0)
				{
					$maxElement[]	= floor($USER[$resource[$resourceID]] / $price);
				}
				else
				{
					$maxElement[]	= 1000000;
				}
            }
            else
            {
                throw new Exception("Unknown Ressource ".$resourceID." at element ".$Element.".");
            }
        }

        if($Element == 239) {
            // Queued Gilberts count toward the 25000 limit, or several orders could pass it.
            $queuedElements = 0;
            if(!empty($PLANET['b_hangar_id'])) {
                foreach(unserialize($PLANET['b_hangar_id']) as $entry) {
                    if($entry[0] == 239) {
                        $queuedElements += $entry[1];
                    }
                }
            }
            $maxElement[]	= max(0, 25000 - $PLANET[$resource[239]] - $queuedElements);
            if($PLANET['b_hangar'] != 0) {
                $maxElement[] = 0;
            }
        }
        if(in_array($Element, $reslist['one'])) {
            $maxElement[]	= max(0, 1 - $PLANET[$resource[$Element]]);
        }

        return min($maxElement);
    }

    public static function getMaxConstructibleRockets($USER, $PLANET, $Missiles = NULL)
    {
        global $resource, $reslist;

        if(!isset($Missiles))
        {
            $Missiles	= array();

            foreach($reslist['missile'] as $elementID)
            {
                $Missiles[$elementID]	= $PLANET[$resource[$elementID]];
            }
        }

        $BuildArray  	  	= !empty($PLANET['b_hangar_id']) ? unserialize($PLANET['b_hangar_id']) : array();
        $MaxMissiles   		= $PLANET[$resource[44]] * 100 * max(Config::get()->silo_factor, 1);

        foreach($BuildArray as $ElementArray) {
            if(isset($Missiles[$ElementArray[0]]))
                $Missiles[$ElementArray[0]] += $ElementArray[1];
        }

        $ActuMissiles  = ($Missiles[502] * 4) + (40 * $Missiles[503]);
        $MissilesSpace = max(0, $MaxMissiles - $ActuMissiles);

        return array(
            502	=> floor($MissilesSpace / 4),
            503	=> floor($MissilesSpace / 40),
        );
    }

    public static function getAvalibleBonus($Element)
    {
        global $pricelist;

        $elementBonus	= array();

        foreach(self::$bonusList as $bonus)
        {
            $temp	= isset($pricelist[$Element]['bonus'][$bonus][0]) ? (float) $pricelist[$Element]['bonus'][$bonus][0] : 0.0;
            if(empty($temp))
            {
                continue;
            }

            $elementBonus[$bonus]	= $pricelist[$Element]['bonus'][$bonus];
        }

        return $elementBonus;
    }
}