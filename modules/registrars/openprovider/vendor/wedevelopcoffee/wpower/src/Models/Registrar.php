<?php
namespace WeDevelopCoffee\wPower\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Punic\Exception;

/**
 * Domain model
 */
class Registrar extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tblregistrars';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['decodedValue'];

    public $timestamps = false;

    /**
     * List all registrars.
     *
     * @return mixed
     */
    public function listRegistrars()
    {
        return self::groupBy('registrar');
    }

    /**
     * Fetch all registrar data for the specific registrar and decode.
     *
     * @return array
     */
    public function getRegistrarData()
    {
        $rawRegistrarData = $this->get();

        $registrarData = [];
        foreach($rawRegistrarData as $key => $data)
        {
            $registrarData [$data->registrar] [$data->setting] = $this->decode($data->value);
        }

        return $registrarData;
    }

    /**
     * Fetch all registrar data for the specific registrar and decode.
     *
     * @return array|string
     */
    public function getByKey($registrar, $key, $defaultValue = '')
    {
        $data = self::where('registrar', $registrar)
            ->where('setting', $key)
            ->first();

        $result = self::decode($data->value);

        if($result == '')
        {
            // on or off typically reflect a yesno type of field.
            if($defaultValue == 'on' || $defaultValue == 'off')
                return 'off';
            else
                return $defaultValue;
        }

        return $result;
    }

    /**
     * Update the registrar key value.
     *
     * @return array|string
     */
    public function updateByKey($registrar, $key, $value = '')
    {
        $value = self::encode($value);
        try {
            $data = self::where('registrar', $registrar)
                ->where('setting', $key)
                ->firstOrFail();
        } catch ( ModelNotFoundException $e)
        {
            // Create the record.
            $data = new Registrar();
            $data->registrar = $registrar;
            $data->setting = $key;
        }

        $data->value = $value;
        $data->save();

        return true;
    }

    /**
     * Get the TLDs
     *
     * @param $registrar
     * @return array
     */
    public function getTlds ($registrar)
    {
        $result = DomainPricing::where('autoreg', $registrar)->get();
        if(empty($result))
            return [];
        $tlds = [];
        foreach($result as $tld)
        {
            $tlds[$tld->extension] = $tld;
        }
        return $tlds;
    }

    /**
     * Estimate how many domains need a transfer.
     *
     * @return mixed
     */
    public function getDecodedValueAttribute()
    {
        return $this->decode($this->value);
    }

    /**
     * Decode the retrieved data.
     *
     * @param $data
     * @return mixed
     */
    protected function decode($data)
    {
        return html_entity_decode(\localAPI('DecryptPassword', ['password2' => $data])['password']);
    }

    /**
     * Decode the retrieved data.
     *
     * @param $data
     * @return mixed
     */
    protected function encode($data)
    {
        return html_entity_decode(\localAPI('EncryptPassword', ['password2' => $data])['password']);
    }

}
