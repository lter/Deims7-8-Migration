# set up the libraries

library(tidyr)
library(dplyr)

# read in the csv file exported from the database with BLOB fields converted to text
# obviously, the query could be run from within an R script if somebody wants to program it.

df_raw <- read.csv("variableExport.csv", header = T, quote = "\"", as.is = T)
df_ref_raw <- select(df_raw, entity_id, variable_id = field_variables_id)

df_ref_raw <- arrange(df_ref_raw, entity_id)

df_ref_datasource_variable <- setNames(data.frame(matrix(ncol = 3, nrow = 0)), c("entity_id", "delta", "variable_id"))

i <- 1

while (i <= nrow(df_ref_raw)) {
  
  entity_id_1 <- df_ref_raw[i,1]
  
  df_ref_sub <- filter(df_ref_raw, entity_id == entity_id_1)
  
  max_delta <- nrow(df_ref_sub) - 1
  
  df_ref_sub$delta <- 0:max_delta
  
  df_ref_datasource_variable <- rbind(df_ref_datasource_variable, df_ref_sub)
  
  i = i + nrow(df_ref_sub)
  
}

df_dsource_vid <- read.csv("exportDataSourceIDs.csv", header = T, as.is = T)

df_ref_datasource_variable$bundle <- "data_source"
df_ref_datasource_variable$langcode <- "en"
df_ref_datasource_variable <- left_join(df_ref_datasource_variable, df_dsource_vid, by = c("entity_id" = "nid"))
df_ref_datasource_variable <- select(df_ref_datasource_variable, bundle, entity_id, revision_id = vid, langcode, delta, field_dsource_variables_target_id = variable_id)

write.csv(df_ref_datasource_variable, file = "upload_dsourceVariablesReference.csv", row.names = F)
