<?php
/**
 * Created by PhpStorm.
 * User: deprecator
 * Date: 21.10.15
 * Time: 16:00
 */

namespace TrueCore\App\Libraries\Payment\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PaymentOrder
 *
 * @property string $code                   -- Internal unique order ID, @see self::generateCode()
 * @property integer $user_id               -- Internal User ID
 * @property string $pp_code                -- Payment processor`s internal order id which is provided in response
 * @property integer $amount                -- Amount of money to be paid in cents
 * @property integer $currency              -- Currency code according to ISO 4217
 * @property integer $margin                -- Site owner`s stated commission in cents(or whatever)
 * @property integer $payment_processor     -- Integer representation of the payment processor in use
 * @property string $order_data             -- Some order data stored in a JSON string
 * @property Carbon $created_at            -- Creation date
 * @property Carbon $updated_at            -- Modification date
 * @property integer $status                -- Internal order status
 * @property string $response_data          -- Response data stored in a JSON string
 * @property string $initiator_ip           -- IP address of the person who wants to pay the order
 * @property string $responder_ip           -- IP address of the respective payment gate
 *
 * @package TrueCore\App\Libraries\Payment\Models
 */
class PaymentOrder extends Model
{

    const STATUS_PENDING    = 10;   // A newly created order
    const STATUS_PROCESSING = 15;   // The order went on processing
    const STATUS_SUCCESSFUL = 30;   // The order was paid successfully
    const STATUS_FAILED     = 20;   // Something went wrong with the payment

    protected $customOrderData = [];

    protected $table = 'payment_order';
    protected $primaryKey = 'code';
    public $incrementing = false;

    protected $casts = [
        'status' => 'integer',
        'amount' => 'integer',
        'margin' => 'integer'
    ];

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'amount', 'margin', 'payment_processor', 'created_at', 'updated_at', 'status'], 'integer'],
            [['code', 'pp_code', 'order_data', 'response_data'], 'string'],
            [['initiator_ip', 'responder_ip'], 'string', 'max' => 20]
        ];
    }

    /**
     * @return array
     */
    public static function getStatusList() : array
    {
        return [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_PROCESSING => 'Исполняется',
            self::STATUS_FAILED => 'Отклонен',
            self::STATUS_SUCCESSFUL => 'Успешно'
        ];
    }

    /**
     * @param int $status
     * @return string
     */
    public static function getStatusTitle($status) : string
    {
        return ((array_key_exists($status, self::getStatusList())) ? self::getStatusList()[$status] : '');
    }

    /**
     * @return array
     */
    public function getCustomParams() : array
    {
        $this->customOrderData = json_decode($this->order_data, true);
        return $this->customOrderData;
    }

    public function getCustomParam($param)
    {
        if(count($this->customOrderData) === 0) {
            $this->customOrderData = $this->getCustomParams();
        }
        if(array_key_exists($param, $this->customOrderData)) {
            return $this->customOrderData[$param];
        }
        return '';
    }

    /**
     * @param array $data
     * @return PaymentOrder
     */
    public static function makeOrder(array $data)
    {
        $order = new self;
        $order->code = self::generateCode();
        $order->amount = (int)($data['amount'] * 100);
        $order->currency = ((array_key_exists('currency', $data) && (is_string($data['currency']) || is_numeric($data['currency']))) ? $data['currency'] : null);
        $order->payment_processor = $data['payment_processor'];
        $order->setOrderData($data['orderData']);
        $order->status = self::STATUS_PENDING;
        $order->response_data = json_encode([], JSON_UNESCAPED_UNICODE);
        $order->initiator_ip = $data['initiator_ip'];

        return $order;
    }

    /**
     * Generates a unique code for the entry
     * @return string
     */
    private static function generateCode() : string
    {
        return (md5(time().'_'.uniqid()));
    }

    /**
     * @param int|float $margin
     */
    public function setMargin($margin)
    {
        // The fee must be greater than or equal 0% yet less than 100%
        $margin = (($margin > 0) ? (($margin < 100) ? $margin : 99) : 0);
        $this->margin = round(($this->amount * $margin) / 100);
    }

    /**
     * @param integer $currency
     * @throws \Exception
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function setExternalCode($code)
    {
        $this->pp_code = $code;
    }

    /**
     * @param array $data
     */
    public function setOrderData(array $data)
    {
        $this->order_data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response)
    {
        $this->response_data = json_encode($response, JSON_UNESCAPED_UNICODE);
        $this->responder_ip = $response['responder_ip'];
    }

    /**
     * @return array
     */
    public function getOrderData() : array
    {
        return json_decode($this->order_data, true);
    }

    /**
     * @return array
     */
    public function getResponse() : array
    {
        return json_decode($this->response_data, true);
    }

    /**
     * Changes order status
     * @param integer $status
     * @return bool
     */
    protected function changeStatus($status) : bool
    {
        $this->status = (int)$status;
        try {
            $this->status = $status;

            // @TODO: somehow make it clear whether the record is in the table already or not | deprecator @ 2018-11-30
            return (!$this->exists || $this->save());
        } catch(\Exception $e) {
            //self::getLogger()->log('Order status change has failed for some reason: '.$e->getMessage(), Logger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * Marks the order as successful
     * @return bool
     */
    public function markAsSuccessful()
    {
        return $this->changeStatus(self::STATUS_SUCCESSFUL);
    }

    /**
     * Marks the order as processing
     * @return bool
     */
    public function markAsProcessing()
    {
        return $this->changeStatus(self::STATUS_PROCESSING);
    }

    /**
     * Marks the order as failed
     * @return bool
     */
    public function markAsFailed()
    {
        return $this->changeStatus(self::STATUS_FAILED);
    }

    /**
     * @param string $code
     * @return Model|null|PaymentOrder
     */
    public static function findOrderByCode(string $code) : ?PaymentOrder
    {
        return self::query()->where('code', '=', $code)->first();
    }

    /**
     * @param string $code
     * @return Model|null|PaymentOrder
     */
    public static function findOrderByPpCode(string $code)
    {
        return self::query()->where('pp_code', '=', $code)->first();
    }
}
