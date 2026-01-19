<?php

namespace iam\models;

/**
 * @OA\Schema(
 *     schema="Profile",
 *     @OA\Property(property="first_name", type="string", title="First Name", example="John"),
 *     @OA\Property(property="middle_name", type="string", title="Middle Name", example="Michael"),
 *     @OA\Property(property="last_name", type="string", title="Last Name", example="Doe"),
 *     @OA\Property(property="email_address", type="string", format="email", title="Email Address", example="john.doe@example.com"),
 *     @OA\Property(property="mobile_number", type="string", title="Mobile Number", example="+254712345678"),
 *     @OA\Property(property="avatar_url", type="string", format="uri", title="Avatar URL", example="https://example.com/avatar.jpg"),
 * )
 */

class Profile extends \iam\hooks\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%profiles}}';
    }
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return
            [
                'profile_id',
                'first_name',
                'middle_name',
                'last_name',
                'email_address',
                'mobile_number',
                'avatar_url',
            ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email_address', 'created_at', 'updated_at'], 'required'],
            [['data'], 'safe'],
            [['created_at', 'updated_at'], 'integer'],
            [['first_name', 'middle_name', 'last_name'], 'string', 'max' => 50],
            [['email_address'], 'string', 'max' => 128],
            [['mobile_number'], 'string', 'max' => 15],
            [['avatar_url'], 'string', 'max' => 255],
            [['email_address'], 'unique'],
            [['mobile_number'], 'unique'],
        ];
    }
    public function getFull_name()
    {
        $names = [$this->first_name];
        if (!empty($this->middle_name)) {
            $names[] = $this->middle_name;
        }
        $names[] = $this->last_name;
        return implode(' ', $names);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['profile_id' => 'profile_id']);
    }
}
