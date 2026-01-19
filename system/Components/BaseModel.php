<?php

namespace  helpers;


class BaseModel extends \yii\base\Model
{
    use traits\Keygen;
    use traits\Status;
    use traits\ServiceConsumer;
    use traits\Like;
}
