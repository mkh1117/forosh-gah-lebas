<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\ProductVariant;

class DuplicateRule implements Rule
{
    protected $currentRecordId; // این ID مربوط به واریانتی است که داریم در فرم Edit ویرایش می‌کنیم.
    protected $postId;          // این ID مربوط به پست والد است که در صفحه Edit از route گرفته می‌شود.

    /**
     * Constructor.
     * @param int|null $currentRecordId The ID of the specific variant being edited, if applicable.
     */
    public function __construct($currentRecordId = null)
    {
        $this->currentRecordId = $currentRecordId;

        // postId را از route parameter 'record' دریافت می‌کنیم.
        // این در حالت edit وجود دارد. در حالت create، null خواهد بود.
        $this->postId = request()->route('record') ?? null;
    }

           public function passes($attribute, $value)
        {
            $val = $value;
            foreach($val as $i=> $v){
               foreach($val as $j=>$vi){
                if ($v['size']==$vi['size'] and $v['color']==$vi['color'] and ($i!=$j)){
                    return false;
                }
               }
            }
            return true;
        }


    public function message()
    {
        // این پیام هم در حالت Create و هم Edit نمایش داده می‌شود.
        return '.این ترکیب رنگ و سایز قبلاً برای این محصول ثبت شده است';
    }
}
