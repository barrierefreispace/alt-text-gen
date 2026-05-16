#
# 功能（中文）：为 sys_file_reference 增加 ALT 生成偏好持久化字段（文风、SEO关键词）。
# Function (English): Add persistent preference fields (style, SEO keywords) to sys_file_reference.
#
CREATE TABLE sys_file_reference (
    tx_barrierefrei_space_style varchar(64) DEFAULT 'formal' NOT NULL,
    tx_barrierefrei_space_seo_keywords varchar(255) DEFAULT '' NOT NULL
);
