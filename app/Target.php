<?php

/**
 * Target eloquent model.
 *
 * PHP Version 7
 *
 * @category Targets
 * @author   Wim De Meester <deepskywim@gmail.com>
 * @license  GPL3 <https://opensource.org/licenses/GPL-3.0>
 * @link     http://www.deepskylog.org
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use deepskylog\AstronomyLibrary\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use deepskylog\AstronomyLibrary\Coordinates\EquatorialCoordinates;
use deepskylog\AstronomyLibrary\Coordinates\GeographicalCoordinates;

/**
 * Target eloquent model.
 *
 * @category Targets
 * @author   Wim De Meester <deepskywim@gmail.com>
 * @license  GPL3 <https://opensource.org/licenses/GPL-3.0>
 * @link     http://www.deepskylog.org
 */
class Target extends Model
{
    private $_contrast;

    private $_popup;

    private $_target = null;

    private $_ephemerides;

    private $_location;

    private $_highestFromToAround;

    protected $fillable = ['name', 'type'];

    // These are the fields that are created dynamically, using the get...Attribute methods.
    protected $appends = ['rise', 'contrast', 'contrast_type', 'contrast_popup',
        'prefMag', 'prefMagEasy', 'rise_popup', 'transit', 'transit_popup',
        'set', 'set_popup', 'bestTime', 'maxAlt', 'maxAlt_popup',
        'highest_from', 'highest_around', 'highest_to', 'highest_alt', ];

    protected $primaryKey = 'name';

    private $_observationType = null;
    private $_targetType = null;

    public $incrementing = false;

    /**
     * Returns the contrast of the target.
     *
     * @return string The contrast of the target
     */
    public function getContrastAttribute(): string
    {
        if (!auth()->guest()) {
            if (!isset($this->_contrast)) {
                $this->_contrast = new \App\Contrast($this);
            }

            return $this->_contrast->contrast;
        } else {
            return '-';
        }
    }

    /**
     * Returns the contrast type of the target, for showing
     * the correct background color.
     *
     * @return string The contrast type of the target
     */
    public function getContrastTypeAttribute(): string
    {
        if (!auth()->guest()) {
            if (!isset($this->_contrast)) {
                $this->_contrast = new \App\Contrast($this);
            }

            return $this->_contrast->contype;
        } else {
            return '-';
        }
    }

    /**
     * Returns the text for the popup with the contrast of the target.
     *
     * @return string The popup with the contrast of the target
     */
    public function getContrastPopupAttribute(): string
    {
        if (!auth()->guest()) {
            if (!isset($this->_contrast)) {
                $this->_contrast = new \App\Contrast($this);
            }

            return $this->_contrast->popup;
        } else {
            return '-';
        }
    }

    /**
     * Returns the preferred magnitude of the target, with
     * the information on the eyepiece / lens to use.
     *
     * @return string The preferred magnitude of the target
     */
    public function getPrefMagAttribute(): string
    {
        if (!auth()->guest()) {
            if (!isset($this->_contrast)) {
                $this->_contrast = new \App\Contrast($this);
            }

            return $this->_contrast->prefMag;
        } else {
            return '-';
        }
    }

    /**
     * Returns the preferred magnitude of the target.
     *
     * @return string The preferred magnitude of the target
     */
    public function getPrefMagEasyAttribute(): string
    {
        if (!auth()->guest()) {
            if (!isset($this->_contrast)) {
                $this->_contrast = new \App\Contrast($this);
            }

            return $this->_contrast->prefMagEasy;
        } else {
            return '-';
        }
    }

    /**
     * Returns the rise time of the target.
     *
     * @return string The rise time of the target
     */
    public function getRiseAttribute(): string
    {
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return Auth::guest() ? '-' : ($this->_target->getRising() ? $this->_target->getRising()
            ->timezone($this->_location->timezone)->format('H:i') : '-');
    }

    /**
     * Returns the popup for the rise time of the target.
     *
     * @return string The popup for the rise time of the target
     */
    public function getRisePopupAttribute(): string
    {
        if (!auth()->guest()) {
            if (!$this->_target) {
                $this->getRiseSetTransit();
            }

            return $this->_popup[0];
        } else {
            return '-';
        }
    }

    /**
     * Returns the transit time of the target.
     *
     * @return string The transit time of the target
     */
    public function getTransitAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_target->getTransit()
            ->timezone($this->_location->timezone)->format('H:i');
    }

    /**
     * Returns the popup for the transit time of the target.
     *
     * @return string The popup for the transit time of the target
     */
    public function getTransitPopupAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_popup[1];
    }

    /**
     * Returns the set time of the target.
     *
     * @return string The set time of the target
     */
    public function getSetAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_target->getSetting() ? $this->_target->getSetting()
            ->timezone($this->_location->timezone)->format('H:i') : '-';
    }

    /**
     * Returns the popup for the set time of the target.
     *
     * @return string The popup for the set time of the target
     */
    public function getSetPopupAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }

        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_popup[2];
    }

    /**
     * Returns the best time of the target.
     *
     * @return string The best time of the target
     */
    public function getBestTimeAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }

        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_target->getBestTimeToObserve() ?
            $this->_target->getBestTimeToObserve()
            ->timezone($this->_location->timezone)->format('H:i') : '-';
    }

    /**
     * Returns the maximum altitude of the target.
     *
     * @return string The maximum altitude of the target
     */
    public function getMaxAltAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_target->getMaxHeightAtNight() ?
            $this->_target->getMaxHeightAtNight()
            ->convertToDegrees() : '-';
    }

    /**
     * Returns the popup for the maximum altitude of the target.
     *
     * @return string The popup for the maximum altitude of the target
     */
    public function getMaxAltPopupAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_popup[3];
    }

    /**
     * Returns the highest altitude of the target.
     *
     * @return string The highest altitude of the target
     */
    public function getHighestAltAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_highestFromToAround[3];
    }

    /**
     * Returns the month from which the highest altitude is reached.
     *
     * @return string Returns the month from which the highest altitude is reached
     */
    public function getHighestFromAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!isset($this->_ephemerides)) {
            $this->getYearEphemerides();
        }

        return $this->_highestFromToAround[0];
    }

    /**
     * Returns the month around which the highest altitude is reached.
     *
     * @return string Returns the month around which the highest altitude is reached
     */
    public function getHighestAroundAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!isset($this->_ephemerides)) {
            $this->getYearEphemerides();
        }

        return $this->_highestFromToAround[1];
    }

    /**
     * Returns the month to which the highest altitude is reached.
     *
     * @return string Returns the month to which the highest altitude is reached
     */
    public function getHighestToAttribute(): string
    {
        if (Auth::guest()) {
            return '-';
        }
        if (!isset($this->_ephemerides)) {
            $this->getYearEphemerides();
        }

        return $this->_highestFromToAround[2];
    }

    /**
     * Returns the information on the rise, transit, and set times of the target.
     *
     * @return None
     */
    public function getRiseSetTransit(): void
    {
        $this->_target = new \deepskylog\AstronomyLibrary\Targets\Target();
        $equa = new EquatorialCoordinates($this->ra, $this->decl);

        // Add equatorial coordinates to the target.
        $this->_target->setEquatorialCoordinates($equa);

        if (!Auth::guest()) {
            if (Auth::user()->stdlocation != 0 && Auth::user()->stdtelescope != 0) {
                if ($this->isNonSolarSystem()) {
                    $datestr = Session::get('date');
                    $date = Carbon::createFromFormat('d/m/Y', $datestr);
                    $date->hour = 12;
                    if ($this->_location == null) {
                        $this->_location = \App\Location::where(
                            'id',
                            Auth::user()->stdlocation
                        )->first();
                    }
                    $location = $this->_location;

                    $geo_coords = new GeographicalCoordinates(
                        $location->longitude,
                        $location->latitude
                    );

                    $date->timezone($this->_location->timezone);

                    $greenwichSiderialTime = Time::apparentSiderialTimeGreenwich(
                        $date
                    );
                    $deltaT = Time::deltaT($date);

                    // Calculate the ephemerids for the target
                    $this->_target->calculateEphemerides(
                        $geo_coords,
                        $greenwichSiderialTime,
                        $deltaT
                    );

                    if ($this->_target->getMaxHeight()->getCoordinate() < 0.0) {
                        $popup[0] = sprintf(
                            _i('%s does not rise above horizon'),
                            $this->name
                        );
                        $popup[2] = $popup[0];
                    } elseif (!$this->_target->getRising()) {
                        $popup[0] = sprintf(_i('%s is circumpolar'), $this->name);
                        $popup[2] = $popup[0];
                    } else {
                        $popup[0] = sprintf(
                            _i('%s rises at %s in %s on ')
                                . $date->isoFormat('LL'),
                            $this->name,
                            $this->_target->getRising()
                                ->timezone($location->timezone)->format('H:i'),
                            $location->name
                        );
                        $popup[2] = sprintf(
                            _i('%s sets at %s in %s on ')
                                . $date->isoFormat('LL'),
                            $this->name,
                            $this->_target->getSetting()
                                ->timezone($location->timezone)->format('H:i'),
                            $location->name
                        );
                    }
                    $popup[1] = sprintf(
                        _i('%s transits at %s in %s on ')
                            . $date->isoFormat('LL'),
                        $this->name,
                        $this->_target->getTransit()
                            ->timezone($location->timezone)->format('H:i'),
                        $location->name
                    );

                    if ($this->_target->getMaxHeightAtNight()->getCoordinate() < 0) {
                        $popup[3] = sprintf(
                            _i('%s does not rise above horizon in %s on ')
                                . $date->isoFormat('LL'),
                            $this->name,
                            $location->name,
                            $datestr
                        );
                    } else {
                        $popup[3] = sprintf(
                            _i('%s reaches an altitude of %s in %s on ')
                                . $date->isoFormat('LL'),
                            $this->name,
                            trim(
                                $this->_target->getMaxHeightAtNight()
                                    ->convertToDegrees()
                            ),
                            $location->name,
                        );
                    }

                    $this->_popup = $popup;
                }
            }
        }
    }

    /**
     * Targets have exactly one target type.
     *
     * @return HasOne The eloquent relationship
     */
    public function type()
    {
        return $this->hasOne('App\TargetType', 'id', 'type');
    }

    /**
     * Targets have exactly one or none constellations.
     *
     * @return HasOne The eloquent relationship
     */
    public function constellation()
    {
        return $this->hasOne('App\Constellation', 'id', 'con');
    }

    /**
     * Returns the atlaspage of the target when the code of the atlas is given.
     *
     * @param string $atlasname The code of the atlas
     *
     * @return string The page where the target can be found in the atlas
     */
    public function atlasPage($atlasname): string
    {
        return $this->$atlasname;
    }

    /**
     * Returns the declination as a human readable string.
     *
     * @return string The declination
     */
    public function declination(): string
    {
        return $this->_target->getEquatorialCoordinates()
            ->getDeclination()->convertToDegrees();
    }

    /**
     * Sets the observation types for the target.
     *
     * @return None
     */
    private function _setObservationType(): void
    {
        $this->_targetType = $this->type()->first();
        $this->_observationType = $this->_targetType
            ->observationType()->first();
    }

    /**
     * Return the observation type and the target type for showing in the
     * detail page.
     *
     * @return string The Observation Type / Target Type
     */
    public function getObservationTypeAttribute(): string
    {
        if ($this->_observationType == null) {
            $this->_setObservationType();
        }

        return _i($this->_observationType['name'])
            . ' / ' . _i($this->_targetType['type']);
    }

    /**
     *  Check if the target is deepsky or a double star.
     *
     * @return bool true if the targer is deepsky or double star
     */
    public function isNonSolarSystem(): bool
    {
        if ($this->_observationType == null) {
            $this->_setObservationType();
        }

        return $this->_observationType['type'] == 'ds'
            || $this->_observationType['type'] == 'double';
    }

    /**
     * Returns the right ascension as a human readable string.
     *
     * @return string The right ascension
     */
    public function ra(): string
    {
        if (!$this->_target) {
            $this->getRiseSetTransit();
        }

        return $this->_target->getEquatorialCoordinates()
            ->getRA()->convertToHours();
    }

    /**
     * Returns the size of the target as a human readable string.
     *
     * @return string The size
     */
    public function size(): string
    {
        $size = '-';
        if ($this->diam1 != 0.0) {
            if ($this->diam1 >= 40.0) {
                if (round($this->diam1 / 60.0) == ($this->diam1 / 60.0)) {
                    if (($this->diam1 / 60.0) > 30.0) {
                        $size = sprintf("%.0f'", $this->diam1 / 60.0);
                    } else {
                        $size = sprintf("%.1f'", $this->diam1 / 60.0);
                    }
                } else {
                    $size = sprintf("%.1f'", $this->diam1 / 60.0);
                }
                if ($this->diam2 != 0.0) {
                    if (round($this->diam2 / 60.0) == ($this->diam2 / 60.0)) {
                        if (($this->diam2 / 60.0) > 30.0) {
                            $size = $size . sprintf("x%.0f'", $this->diam2 / 60.0);
                        } else {
                            $size = $size . sprintf("x%.1f'", $this->diam2 / 60.0);
                        }
                    } else {
                        $size = $size . sprintf("x%.1f'", $this->diam2 / 60.0);
                    }
                }
            } else {
                $size = sprintf('%.1f"', $this->diam1);
                if ($this->diam2 != 0.0) {
                    $size = $size . sprintf('x%.1f"', $this->diam2);
                }
            }
        }

        return $size;
    }

    /**
     * Returns the Field Of View of the target to be used in the aladin script.
     *
     * @return string The Field Of View
     */
    public function getFOV(): string
    {
        if (preg_match('/(?i)^AA\d*STAR$/', $this->type)
            || preg_match('/(?i)^PLNNB$/', $this->type)
            || $this->diam1 == 0 && $this->diam2 == 0
        ) {
            $fov = 1;
        } else {
            $fov = 2 * max($this->diam1, $this->diam2) / 3600;
        }

        return $fov;
    }

    /**
     * Returns the ra and dec of the target to be used in the aladin script.
     *
     * @return string The coordinates
     */
    public function raDecToAladin(): string
    {
        return str_replace(
            '  ',
            ' +',
            str_replace(
                'h',
                ' ',
                str_replace(
                    'm',
                    ' ',
                    str_replace(
                        's',
                        '',
                        str_replace(
                            '°',
                            ' ',
                            str_replace(
                                "'",
                                ' ',
                                str_replace(
                                    '"',
                                    '',
                                    $this->_target->getEquatorialCoordinates()
                                        ->getRA()->convertToHours()
                                    . ' '
                                    . $this->_target->getEquatorialCoordinates()
                                        ->getDeclination()->convertToDegrees()
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Returns the ephemerids for a whole year.
     * The ephemerids are calculated the first and the fifteenth of the month.
     *
     * @return array the ephemerides for a whole year
     */
    public function getYearEphemerides(): array
    {
        if (auth()->guest()) {
            return $this->_ephemerides;
        }
        if (isset($this->_ephemerides)) {
            return $this->_ephemerides;
        } else {
            if ($this->_location == null) {
                $this->_location = \App\Location::where(
                    'id',
                    Auth::user()->stdlocation
                )->first();
            }
            $location = $this->_location;
            $cnt = 0;

            $geo_coords = new GeographicalCoordinates(
                $location->longitude,
                $location->latitude
            );

            $target = new
                \deepskylog\AstronomyLibrary\Targets\Target();
            $equa = new EquatorialCoordinates($this->ra, $this->decl);

            // Add equatorial coordinates to the target.
            $target->setEquatorialCoordinates($equa);

            for ($i = 1; $i < 13; $i++) {
                for ($j = 1; $j < 16; $j = $j + 14) {
                    $datestr = sprintf('%02d', $j) . '/' . sprintf('%02d', $i) . '/'
                        . \Carbon\Carbon::now()->format('Y');
                    $date = Carbon::createFromFormat('d/m/Y', $datestr);
                    $date->hour = 12;
                    $date->timezone($this->_location->timezone);
                    $ephemerides[$cnt]['date'] = $date;

                    $greenwichSiderialTime = Time::apparentSiderialTimeGreenwich(
                        $date
                    );
                    $deltaT = Time::deltaT($date);

                    // Calculate the ephemerids for the target
                    $target->calculateEphemerides(
                        $geo_coords,
                        $greenwichSiderialTime,
                        $deltaT
                    );

                    $nightephemerides = date_sun_info(
                        $date->getTimestamp(),
                        $location->latitude,
                        $location->longitude
                    );
                    $ephemerides[$cnt]['max_alt'] = trim(
                        $target->getMaxHeightAtNight()->convertToDegrees()
                    );
                    $ephemerides[$cnt]['transit'] = $target->getTransit()
                        ->timezone($this->_location->timezone)->format('H:i');
                    $ephemerides[$cnt]['rise'] = $target->getRising() ?
                        $target->getRising()->timezone($this->_location->timezone)
                        ->format('H:i') : '-';
                    $ephemerides[$cnt]['set'] = $target->getSetting() ?
                        $target->getSetting()->timezone($this->_location->timezone)
                        ->format('H:i') : '-';
                    $ephemerides[$cnt]['transitCarbon'] = $target->getTransit()
                        ->timezone($this->_location->timezone);
                    $ephemerides[$cnt]['riseCarbon'] = $target->getRising() ?
                        $target->getRising()->timezone($this->_location->timezone)
                        : '-';
                    $ephemerides[$cnt]['setCarbon'] = $target->getSetting() ?
                        $target->getSetting()->timezone($this->_location->timezone)
                        : '-';

                    $ephemerides[$cnt]['astronomical_twilight_end'] = is_bool(
                        $nightephemerides['astronomical_twilight_end']
                    ) ? null :
                        $date->copy()
                        ->setTimeFromTimeString(
                            date(
                                'H:i',
                                $nightephemerides['astronomical_twilight_end']
                            )
                        )->timezone($this->_location->timezone);

                    $ephemerides[$cnt]['astronomical_twilight_begin'] = is_bool(
                        $nightephemerides['astronomical_twilight_begin']
                    ) ? null :
                    $date->copy()
                        ->setTimeFromTimeString(
                            date(
                                'H:i',
                                $nightephemerides['astronomical_twilight_begin']
                            )
                        )->timezone($this->_location->timezone);

                    $ephemerides[$cnt]['nautical_twilight_end'] = is_bool(
                        $nightephemerides['nautical_twilight_end']
                    ) ? null : $date->copy()
                        ->setTimeFromTimeString(
                            date('H:i', $nightephemerides['nautical_twilight_end'])
                        )->timezone($this->_location->timezone);

                    $ephemerides[$cnt]['nautical_twilight_begin'] = is_bool(
                        $nightephemerides['nautical_twilight_begin']
                    ) ? null : $date->copy()
                        ->setTimeFromTimeString(
                            date('H:i', $nightephemerides['nautical_twilight_begin'])
                        )->timezone($this->_location->timezone);

                    if ($ephemerides[$cnt]['astronomical_twilight_end'] > $ephemerides[$cnt]['astronomical_twilight_begin']) {
                        $ephemerides[$cnt]['astronomical_twilight_begin']->addDay();
                    }
                    if ($ephemerides[$cnt]['nautical_twilight_end'] > $ephemerides[$cnt]['nautical_twilight_begin']) {
                        $ephemerides[$cnt]['nautical_twilight_begin']->addDay();
                    }
                    $ephemerides[$cnt]['count'] = ($j == 1) ? '' : $i;

                    $cnt++;
                }
            }

            // Setting the classes for the different colors
            $cnt = 0;
            foreach ($ephemerides as $ephem) {
                // Green if the max_alt does not change. This means that the
                // altitude is maximal
                if (($ephem['max_alt'] != '-'
                    && $ephemerides[($cnt + 1) % 24]['max_alt'] != '-')
                    && (($ephem['max_alt'] == $ephemerides[($cnt + 1) % 24]['max_alt'])
                    || ($ephem['max_alt'] == $ephemerides[($cnt + 23) % 24]['max_alt']))
                ) {
                    $ephemerides[$cnt]['max_alt_color'] = 'ephemeridesgreen';
                    $ephemerides[$cnt]['max_alt_popup']
                        = _i('%s reaches its highest altitude of the year', $this->name);
                } else {
                    $ephemerides[$cnt]['max_alt_color'] = '';
                    $ephemerides[$cnt]['max_alt_popup'] = '';
                }

                // Green if the transit is during astronomical twilight
                // Yellow if the transit is during nautical twilight
                $time = $ephem['date']->setTimeZone($location->timezone)->copy()
                    ->setTimeFromTimeString($ephem['transit']);
                if ($time->format('H') < 12) {
                    $time->addDay();
                }

                if ($ephem['max_alt'] != '-') {
                    if ($ephem['astronomical_twilight_end'] != null
                        && $time->between(
                            $ephem['astronomical_twilight_begin'],
                            $ephem['astronomical_twilight_end']
                        )
                    ) {
                        // Also add a popup explaining the color code: Issue 416
                        $ephemerides[$cnt]['transit_color'] = 'ephemeridesgreen';
                        $ephemerides[$cnt]['transit_popup'] = _i('%s reaches its highest altitude during the astronomical night', $this->name);
                    } elseif ($ephem['nautical_twilight_end'] != null
                        && $time->between(
                            $ephem['nautical_twilight_begin'],
                            $ephem['nautical_twilight_end']
                        )
                    ) {
                        $ephemerides[$cnt]['transit_color'] = 'ephemeridesyellow';
                        $ephemerides[$cnt]['transit_popup'] = _i('%s reaches its highest altitude during the nautical twilight', $this->name);
                    } else {
                        $ephemerides[$cnt]['transit_color'] = '';
                        $ephemerides[$cnt]['transit_popup'] = '';
                    }
                } else {
                    $ephemerides[$cnt]['transit_color'] = '';
                    $ephemerides[$cnt]['transit_popup'] = '';
                }

                $ephemerides[$cnt]['rise_color'] = '';
                $ephemerides[$cnt]['rise_popup'] = '';

                if ($ephem['max_alt'] == '-') {
                    $ephemerides[$cnt]['rise_color'] = '';
                } else {
                    if ($ephem['rise'] == '-') {
                        if ($ephem['astronomical_twilight_end'] != null) {
                            $ephemerides[$cnt]['rise_popup'] = _i('%s is visible during the night', $this->name);
                            $ephemerides[$cnt]['rise_color'] = 'ephemeridesgreen';
                        } elseif ($ephem['nautical_twilight_end'] != null) {
                            $ephemerides[$cnt]['rise_popup'] = _i('%s is visible during the nautical twilight', $this->name);
                            $ephemerides[$cnt]['rise_color'] = 'ephemeridesyellow';
                        }
                    }
                    if ($ephem['astronomical_twilight_end'] != null
                        && $this->_checkNightHourMinutePeriodOverlap(
                            $ephem['riseCarbon'],
                            $ephem['setCarbon'],
                            $ephem['astronomical_twilight_end'],
                            $ephem['astronomical_twilight_begin']
                        )
                    ) {
                        $ephemerides[$cnt]['rise_popup'] = _i(
                            '%s is visible during the night',
                            $this->name
                        );
                        $ephemerides[$cnt]['rise_color'] = 'ephemeridesgreen';
                    } elseif ($ephem['nautical_twilight_end'] != null
                        && $this->_checkNightHourMinutePeriodOverlap(
                            $ephem['riseCarbon'],
                            $ephem['setCarbon'],
                            $ephem['nautical_twilight_end'],
                            $ephem['nautical_twilight_begin']
                        )
                    ) {
                        $ephemerides[$cnt]['rise_color'] = 'ephemeridesyellow';
                        $ephemerides[$cnt]['rise_popup'] = _i(
                            '%s is visible during the nautical twilight',
                            $this->name
                        );
                    }
                }

                $cnt++;
            }

            $this->_ephemerides = $ephemerides;

            $collection = collect($ephemerides);
            $max_alt = $collection->max('max_alt');

            $filter = $collection->filter(
                function ($value) use ($max_alt) {
                    if ($value['max_alt'] == $max_alt) {
                        return true;
                    }
                }
            );

            $months = $filter->keys();

            if ($months->min() == 0 && $months->max() == 23) {
                $missing = collect(range(0, 23))->diff($months);

                for ($i = 0; $i < $missing->min(); $i++) {
                    $months[$i] += 24;
                }
            }
            $around = ($months->min()
                + ($months->max() - $months->min()) / 2) % 24 + 1;
            $from = $months->min() % 24 + 1;
            $to = $months->max() % 24 + 1;

            $this->_highestFromToAround[0] = $this->_convertToMonth($from);
            $this->_highestFromToAround[1] = $this->_convertToMonth($around);
            $this->_highestFromToAround[2] = $this->_convertToMonth($to);
            $this->_highestFromToAround[3] = $max_alt;

            return $ephemerides;
        }
    }

    /**
     * Converts a number from 1 to 24 to the name of the month.
     *
     * @param int $number The number of the half-month
     *
     * @return string The name of the month
     */
    private function _convertToMonth($number): string
    {
        $date = Carbon::now()->month($number / 2);

        return ($number % 2 ? _i('mid') : _i('begin'))
                . ' '
                . $date->isoFormat('MMMM');
    }

    /**
     * Checks if there is an overlap between the two given time periods.
     *
     * @param Carbon $firststart  the start of the first time interval
     * @param Carbon $firstend    the end of the first time interval
     * @param Carbon $secondstart the start of the second time interval
     * @param Carbon $secondend   the end of the second time interval
     *
     * @return bool true if the two time intervals overlap
     */
    private function _checkNightHourMinutePeriodOverlap(
        Carbon $firststart,
        Carbon $firstend,
        Carbon $secondstart,
        Carbon $secondend
    ) {
        if ($secondstart->lt($secondend)) {
            return ($firststart->gt($secondstart)
                 && $firststart->lt($secondend))
                 || ($firstend->gt($secondstart)
                 && $firstend->lt($secondend))
                 || ($firststart->lt($secondend)
                 && $firstend->gt($secondend))
                 || ($firststart->lt($secondstart)
                 && $firststart->gt($firstend))
                 || ($firstend->gt($secondend)
                 && $firststart->gt($firstend));
        } else {
            return $firststart->gt($secondstart)
                 || $firststart->lt($secondend)
                 || $firstend->gt($secondstart)
                 || $firstend->lt($secondend)
                 || ($firststart->lt($secondstart)
                 && $firstend->gt($secondend)
                 && $firststart->gt($firstend));
        }
    }

    /**
     * Get a list with the nearby objects.
     *
     * @param int $dist The distance in arcminutes
     *
     * @return Collection The list with the nearby objects
     */
    public function getNearbyObjects($dist)
    {
        $dra = 0.0011 * $dist / cos($this->decl / 180.0 * 3.1415926535);

        return self::where('ra', '>', $this->ra - $dra)
            ->where('ra', '<', $this->ra + $dra)
            ->where('decl', '>', $this->decl - $dist / 60.0)
            ->where('decl', '<', $this->decl + $dist / 60.0);
    }

    /**
     * Returns the constellation of this target.
     *
     * @return string the constellation this target belongs to
     */
    public function getConstellation()
    {
        return \App\Constellation::where('id', $this->con)->first()->name;
    }
}
