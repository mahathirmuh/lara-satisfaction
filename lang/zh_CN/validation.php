<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute 必须接受。',
    'accepted_if' => '当 :other 为 :value 时，:attribute 必须接受。',
    'active_url' => ':attribute 必须是有效的 URL。',
    'after' => ':attribute 必须是 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是 :date 或之后的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、连字符和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'any_of' => ':attribute 无效。',
    'array' => ':attribute 必须是数组。',
    'array_keys' => ':attribute 只能包含以下键：:values。',
    'ascii' => ':attribute 只能包含单字节的字母、数字和符号。',
    'base64' => ':attribute 必须是有效的 Base64 字符串。',
    'before' => ':attribute 必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是 :date 或之前的日期。',
    'between' => [
        'array' => ':attribute 必须包含 :min 到 :max 项。',
        'file' => ':attribute 的大小必须介于 :min 和 :max KB 之间。',
        'numeric' => ':attribute 必须介于 :min 和 :max 之间。',
        'string' => ':attribute 的长度必须介于 :min 和 :max 个字符之间。',
    ],
    'boolean' => ':attribute 必须为 true 或 false。',
    'can' => ':attribute 包含未经授权的值。',
    'confirmed' => ':attribute 与确认值不一致。',
    'contains' => ':attribute 缺少必需的值。',
    'current_password' => '密码不正确。',
    'date' => ':attribute 必须是有效的日期。',
    'date_equals' => ':attribute 必须是等于 :date 的日期。',
    'date_format' => ':attribute 必须符合 :format 格式。',
    'decimal' => ':attribute 必须有 :decimal 位小数。',
    'declined' => ':attribute 必须拒绝。',
    'declined_if' => '当 :other 为 :value 时，:attribute 必须拒绝。',
    'different' => ':attribute 与 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须是 :min 到 :max 位数字。',
    'dimensions' => ':attribute 的图片尺寸无效。',
    'distinct' => ':attribute 存在重复值。',
    'doesnt_contain' => ':attribute 不能包含以下任何一项：:values。',
    'doesnt_end_with' => ':attribute 不能以下列之一结尾：:values。',
    'doesnt_start_with' => ':attribute 不能以下列之一开头：:values。',
    'email' => ':attribute 必须是有效的电子邮件地址。',
    'encoding' => ':attribute 必须使用 :encoding 编码。',
    'ends_with' => ':attribute 必须以下列之一结尾：:values。',
    'enum' => '所选的 :attribute 无效。',
    'exists' => '所选的 :attribute 无效。',
    'extensions' => ':attribute 的扩展名必须是以下之一：:values。',
    'file' => ':attribute 必须是文件。',
    'filled' => ':attribute 必须有值。',
    'gt' => [
        'array' => ':attribute 的项数必须多于 :value 项。',
        'file' => ':attribute 的大小必须大于 :value KB。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 的长度必须大于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 的项数必须大于或等于 :value 项。',
        'file' => ':attribute 的大小必须大于或等于 :value KB。',
        'numeric' => ':attribute 必须大于或等于 :value。',
        'string' => ':attribute 的长度必须大于或等于 :value 个字符。',
    ],
    'hex_color' => ':attribute 必须是有效的十六进制颜色值。',
    'image' => ':attribute 必须是图片。',
    'in' => '所选的 :attribute 无效。',
    'in_array' => ':attribute 必须存在于 :other 中。',
    'in_array_keys' => ':attribute 必须至少包含以下键之一：:values。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是有效的 IP 地址。',
    'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
    'json' => ':attribute 必须是有效的 JSON 字符串。',
    'list' => ':attribute 必须是列表。',
    'lowercase' => ':attribute 必须是小写。',
    'lt' => [
        'array' => ':attribute 的项数必须少于 :value 项。',
        'file' => ':attribute 的大小必须小于 :value KB。',
        'numeric' => ':attribute 必须小于 :value。',
        'string' => ':attribute 的长度必须小于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 的项数不能超过 :value 项。',
        'file' => ':attribute 的大小必须小于或等于 :value KB。',
        'numeric' => ':attribute 必须小于或等于 :value。',
        'string' => ':attribute 的长度必须小于或等于 :value 个字符。',
    ],
    'mac_address' => ':attribute 必须是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 的项数不能超过 :max 项。',
        'file' => ':attribute 的大小不能超过 :max KB。',
        'numeric' => ':attribute 不能大于 :max。',
        'string' => ':attribute 的长度不能超过 :max 个字符。',
    ],
    'max_digits' => ':attribute 的位数不能超过 :max 位。',
    'mimes' => ':attribute 必须是以下类型的文件：:values。',
    'mimetypes' => ':attribute 必须是以下类型的文件：:values。',
    'min' => [
        'array' => ':attribute 至少包含 :min 项。',
        'file' => ':attribute 的大小不能小于 :min KB。',
        'numeric' => ':attribute 不能小于 :min。',
        'string' => ':attribute 的长度不能少于 :min 个字符。',
    ],
    'min_digits' => ':attribute 的位数不能少于 :min 位。',
    'missing' => ':attribute 必须不存在。',
    'missing_if' => '当 :other 为 :value 时，:attribute 必须不存在。',
    'missing_unless' => '除非 :other 为 :value，否则 :attribute 必须不存在。',
    'missing_with' => '当 :values 存在时，:attribute 必须不存在。',
    'missing_with_all' => '当 :values 全部存在时，:attribute 必须不存在。',
    'multiple_of' => ':attribute 必须是 :value 的倍数。',
    'not_in' => '所选的 :attribute 无效。',
    'not_regex' => ':attribute 格式无效。',
    'numeric' => ':attribute 必须是数字。',
    'password' => [
        'letters' => ':attribute 必须至少包含一个字母。',
        'mixed' => ':attribute 必须至少包含一个大写字母和一个小写字母。',
        'numbers' => ':attribute 必须至少包含一个数字。',
        'symbols' => ':attribute 必须至少包含一个符号。',
        'uncompromised' => '给定的 :attribute 已出现在数据泄露中，请选择其他 :attribute。',
    ],
    'present' => ':attribute 必须存在。',
    'present_if' => '当 :other 为 :value 时，:attribute 必须存在。',
    'present_unless' => '除非 :other 为 :value，否则 :attribute 必须存在。',
    'present_with' => '当 :values 存在时，:attribute 必须存在。',
    'present_with_all' => '当 :values 全部存在时，:attribute 必须存在。',
    'prohibited' => ':attribute 禁止填写。',
    'prohibited_if' => '当 :other 为 :value 时，:attribute 禁止填写。',
    'prohibited_if_accepted' => '当 :other 已接受时，:attribute 禁止填写。',
    'prohibited_if_declined' => '当 :other 已拒绝时，:attribute 禁止填写。',
    'prohibited_unless' => '除非 :other 在 :values 中，否则 :attribute 禁止填写。',
    'prohibits' => ':attribute 存在时，不允许出现 :other。',
    'regex' => ':attribute 格式无效。',
    'required' => ':attribute 不能为空。',
    'required_array_keys' => ':attribute 必须包含以下条目：:values。',
    'required_if' => '当 :other 为 :value 时，:attribute 不能为空。',
    'required_if_accepted' => '当 :other 已接受时，:attribute 不能为空。',
    'required_if_declined' => '当 :other 已拒绝时，:attribute 不能为空。',
    'required_unless' => '除非 :other 在 :values 中，否则 :attribute 不能为空。',
    'required_with' => '当 :values 存在时，:attribute 不能为空。',
    'required_with_all' => '当 :values 全部存在时，:attribute 不能为空。',
    'required_without' => '当 :values 不存在时，:attribute 不能为空。',
    'required_without_all' => '当 :values 均不存在时，:attribute 不能为空。',
    'same' => ':attribute 与 :other 必须一致。',
    'size' => [
        'array' => ':attribute 必须包含 :size 项。',
        'file' => ':attribute 的大小必须为 :size KB。',
        'numeric' => ':attribute 必须等于 :size。',
        'string' => ':attribute 的长度必须为 :size 个字符。',
    ],
    'starts_with' => ':attribute 必须以下列之一开头：:values。',
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是有效的时区。',
    'unique' => ':attribute 已经存在。',
    'uploaded' => ':attribute 上传失败。',
    'uppercase' => ':attribute 必须是大写。',
    'url' => ':attribute 必须是有效的 URL。',
    'ulid' => ':attribute 必须是有效的 ULID。',
    'uuid' => ':attribute 必须是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'code' => '代码',
        'color' => '颜色',
        'company_id' => '公司',
        'department_id' => '部门',
        'department_section_id' => '科室',
        'description' => '描述',
        'email' => '邮箱',
        'employee_id' => '员工编号',
        'end_date' => '结束日期',
        'help_text' => '帮助文本',
        'icon' => '图标',
        'is_active' => '启用状态',
        'is_required' => '是否必填',
        'job_title' => '职位',
        'job_titles' => '职位',
        'locale' => '语言',
        'manager_job_titles' => '经理职位',
        'name' => '名称',
        'password' => '密码',
        'question' => '问题',
        'sort_order' => '排序',
        'start_date' => '开始日期',
        'status' => '状态',
        'title' => '标题',
        'type' => '类型',
        'username' => '用户名',
    ],

];
