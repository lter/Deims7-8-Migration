

library(tidyr)
library(dplyr)
library(stringr)


df_raw <- read.csv("variableExport_code_unit.csv", header = T, as.is = T)

#check for nid vs. variables ID - uncomment the next three lines

#df_all_nids <- read.csv("../exportNIDs.csv", header = T, as.is = T)

#df_conflict <- inner_join(df_all_nids, df_raw, by = c("nid" = "field_variables_id"))

#write.csv(df_conflict, file = "conflictingNIDs.csv", row.names = F)

#all conflicts are in ntl_sample.. content types which we are not moving.

#since this is a new content type, make sure there are no nid conflicts between this and D7 as well as D8
#remember that D8 now has code definition content type with nids 1 - 4999
#and units content type uses nids 5000 - 9999


#parse the date format into a column

df_dates_raw <- filter(df_raw, field_variables_type == "date")
df_dates_raw <- select(df_dates_raw, variables_data, field_variables_id)

df_dates_all <- setNames(data.frame(matrix(ncol = 2, nrow = 0)), c("variable_id", "dateFormat"))

for (i in 1:nrow(df_dates_raw)) {
  
  variable_id <- df_dates_raw[i,2]
  
  raw_string <- df_dates_raw[i,1]
  
  date_string <- str_split_fixed(raw_string, "s:22:\"data_explorer_settings\"", n = 2)
  
  date_string_split <- str_split_fixed(date_string[1,1], "s:\\d+:", n = Inf)
  
  date_text_split <- str_split_fixed(date_string_split[1,3], "\"", n = Inf)
  date_text <- date_text_split[1,2]
  
  df_dates_row <- data.frame("variable_id" = variable_id,
                             "dateFormat" = date_text)
  
  df_dates_all <- rbind(df_dates_all, df_dates_row)
  
  
}

df_dates_combined <- left_join(df_raw, df_dates_all, by = c("field_variables_id" = "variable_id"))


#parse the missing value code/definition into two columns

df_mv_raw <- select(df_raw, variables_missing_values, field_variables_id)

df_mv_all <- setNames(data.frame(matrix(ncol = 3, nrow = 0)), c("variable_id", "mv_code", "mv_definition"))

pattern_num <- "i:[[[:punct:]]\\d]+"

for (i in 1:nrow(df_mv_raw)) {
  
  variable_id <- df_mv_raw[i,2]
  
  raw_string <- df_mv_raw[i,1]
  
  if (nchar(as.character(raw_string)) > 9){
    
    mv_string_split <- str_split_fixed(raw_string, "s:\\d+:", n = Inf)
    
    
    if (str_detect(mv_string_split[1,1], pattern_num)) {
      
      mv_extr <- str_extract(mv_string_split[1,1], pattern_num)
      mv_code <- str_extract_all(mv_extr, "[-\\d]+")
      mv_code <- as.character(mv_code[[1]][1])
      
      mv_def_split <- str_split_fixed(mv_string_split[1,2], "\"", n = Inf)
      mv_def <- mv_def_split[1,2]
      
      
    } else {
      
      mv_code_split <- str_split_fixed(mv_string_split[1,2], "\"", n = Inf)
      mv_code <- mv_code_split[1,2]
      
      mv_def_split <- str_split_fixed(mv_string_split[1,3], "\"", n = Inf)
      mv_def <- mv_def_split[1,2]
    }
    
    df_mv_row <- data.frame("variable_id" = variable_id,
                            "mv_code" = str_trim(mv_code),
                            "mv_definition" = str_trim(mv_def))
    
    df_mv_all <- rbind(df_mv_all, df_mv_row)
  }
  
  
}

df_dates_mv_combined <- left_join(df_dates_combined, df_mv_all, by = c("field_variables_id" = "variable_id"))

# parse minimum and maximum values into two columns

df_minmax_raw <- filter(df_raw, field_variables_type == "physical")
df_minmax_raw <- select(df_minmax_raw, variables_data, field_variables_id)

df_minmax_all <- setNames(data.frame(matrix(ncol = 4, nrow = 0)), c("variable_id", "maximum", "minimum", "precision"))

for (i in 1:nrow(df_minmax_raw)) {
  
  variable_id <- df_minmax_raw[i,2]
  
  raw_string <- df_minmax_raw[i,1]
  
  minmax_string <- str_split_fixed(raw_string, "s:22:\"data_explorer_settings\"", n = 2)
  
  minmax_string_split <- str_split_fixed(minmax_string[1,1], "s:\\d+:", n = Inf)
  
  if(length(minmax_string_split) > 8){
  
    minmax_max_split <- str_split_fixed(minmax_string_split[1,5], "\"", n = Inf)
    minmax_max <- minmax_max_split[1,2]
    
    minmax_min_split <- str_split_fixed(minmax_string_split[1,7], "\"", n = Inf)
    minmax_min <- minmax_min_split[1,2]
    
    minmax_prec_split <- str_split_fixed(minmax_string_split[1,9], "\"", n = Inf)
    minmax_prec <- minmax_prec_split[1,2]
    
    df_minmax_row <- data.frame("variable_id" = variable_id,
                                "maximum" = minmax_max,
                                "minimum" = minmax_min,
                                "precision" = minmax_prec)
    
    df_minmax_all <- rbind(df_minmax_all, df_minmax_row)
    
  }
  
}

df_dates_mv_minmax_combined <- left_join(df_dates_mv_combined, df_minmax_all, by = c("field_variables_id" = "variable_id"))


df_variables_nodes <- select(df_dates_mv_minmax_combined, -entity_id, -variables_data, -variables_missing_values)

write.csv(df_variables_nodes, file = "uploadVariablesNode.csv", row.names = F, na = "")

