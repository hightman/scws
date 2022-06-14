/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 022727bf5364305181b88c1595c1b027ac88e852 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_scws_open, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_TYPE_MASK_EX(arginfo_scws_new, 0, 0, SimpleCWS, MAY_BE_FALSE)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_close, 0, 1, IS_VOID, 0)
	ZEND_ARG_INFO(0, handle)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_add_dict, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, dict_path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, mode, IS_LONG, 0, "SCWS_XDICT_XDB")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_set_charset, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, charset, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_scws_set_dict arginfo_scws_add_dict

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_set_rule, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, rule_path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_set_ignore, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, yes, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_set_multi, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, multi, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_scws_set_duality arginfo_scws_set_ignore

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_send_text, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_get_result, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_INFO(0, handle)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_get_tops, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, limit, IS_LONG, 0, "10")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, attr, IS_STRING, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_has_word, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, attr, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_get_words, 0, 2, IS_ARRAY, 0)
	ZEND_ARG_INFO(0, handle)
	ZEND_ARG_TYPE_INFO(0, attr, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_scws_version, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_close, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_add_dict, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, dict_path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, mode, IS_LONG, 0, "SCWS_XDICT_XDB")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_set_charset, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, charset, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_SimpleCWS_set_dict arginfo_class_SimpleCWS_add_dict

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_set_rule, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, rule_path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_set_ignore, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, yes, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_set_multi, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, multi, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_SimpleCWS_set_duality arginfo_class_SimpleCWS_set_ignore

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_send_text, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_get_result, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_get_tops, 0, 0, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, limit, IS_LONG, 0, "10")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, attr, IS_STRING, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_has_word, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, attr, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_get_words, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, attr, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_TENTATIVE_RETURN_TYPE_INFO_EX(arginfo_class_SimpleCWS_version, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()


ZEND_FUNCTION(scws_open);
ZEND_FUNCTION(scws_new);
ZEND_FUNCTION(scws_close);
ZEND_FUNCTION(scws_add_dict);
ZEND_FUNCTION(scws_set_charset);
ZEND_FUNCTION(scws_set_dict);
ZEND_FUNCTION(scws_set_rule);
ZEND_FUNCTION(scws_set_ignore);
ZEND_FUNCTION(scws_set_multi);
ZEND_FUNCTION(scws_set_duality);
ZEND_FUNCTION(scws_send_text);
ZEND_FUNCTION(scws_get_result);
ZEND_FUNCTION(scws_get_tops);
ZEND_FUNCTION(scws_has_word);
ZEND_FUNCTION(scws_get_words);
ZEND_FUNCTION(scws_version);


static const zend_function_entry ext_functions[] = {
	ZEND_FE(scws_open, arginfo_scws_open)
	ZEND_FE(scws_new, arginfo_scws_new)
	ZEND_FE(scws_close, arginfo_scws_close)
	ZEND_FE(scws_add_dict, arginfo_scws_add_dict)
	ZEND_FE(scws_set_charset, arginfo_scws_set_charset)
	ZEND_FE(scws_set_dict, arginfo_scws_set_dict)
	ZEND_FE(scws_set_rule, arginfo_scws_set_rule)
	ZEND_FE(scws_set_ignore, arginfo_scws_set_ignore)
	ZEND_FE(scws_set_multi, arginfo_scws_set_multi)
	ZEND_FE(scws_set_duality, arginfo_scws_set_duality)
	ZEND_FE(scws_send_text, arginfo_scws_send_text)
	ZEND_FE(scws_get_result, arginfo_scws_get_result)
	ZEND_FE(scws_get_tops, arginfo_scws_get_tops)
	ZEND_FE(scws_has_word, arginfo_scws_has_word)
	ZEND_FE(scws_get_words, arginfo_scws_get_words)
	ZEND_FE(scws_version, arginfo_scws_version)
	ZEND_FE_END
};


static const zend_function_entry class_SimpleCWS_methods[] = {
	ZEND_ME_MAPPING(close, scws_close, arginfo_class_SimpleCWS_close, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(add_dict, scws_add_dict, arginfo_class_SimpleCWS_add_dict, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(set_charset, scws_set_charset, arginfo_class_SimpleCWS_set_charset, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(set_dict, scws_set_dict, arginfo_class_SimpleCWS_set_dict, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(set_rule, scws_set_rule, arginfo_class_SimpleCWS_set_rule, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(set_ignore, scws_set_ignore, arginfo_class_SimpleCWS_set_ignore, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(set_multi, scws_set_multi, arginfo_class_SimpleCWS_set_multi, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(set_duality, scws_set_duality, arginfo_class_SimpleCWS_set_duality, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(send_text, scws_send_text, arginfo_class_SimpleCWS_send_text, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(get_result, scws_get_result, arginfo_class_SimpleCWS_get_result, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(get_tops, scws_get_tops, arginfo_class_SimpleCWS_get_tops, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(has_word, scws_has_word, arginfo_class_SimpleCWS_has_word, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(get_words, scws_get_words, arginfo_class_SimpleCWS_get_words, ZEND_ACC_PUBLIC)
	ZEND_ME_MAPPING(version, scws_version, arginfo_class_SimpleCWS_version, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_SimpleCWS(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "SimpleCWS", class_SimpleCWS_methods);
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL;

	zval property_handle_default_value;
	ZVAL_NULL(&property_handle_default_value);
	zend_string *property_handle_name = zend_string_init("handle", sizeof("handle") - 1, 1);
	zend_declare_property_ex(class_entry, property_handle_name, &property_handle_default_value, ZEND_ACC_PUBLIC, NULL);
	zend_string_release(property_handle_name);

	return class_entry;
}
