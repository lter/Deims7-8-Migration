# set up the libraries

library(tidyr)
library(dplyr)
library(stringr)

# read in the csv file exported from the database with BLOB fields converted to text
# obviously, the query could be run from within an R script if somebody wants to program it.

df_raw <- read.csv("variableExport.csv", header = T, quote = "\"", as.is = T)

df_de_raw <- select(df_raw, variables_data, field_variables_id, field_variables_name, field_variables_label)


df_dataexplorer <- setNames(data.frame(matrix(ncol = 3, nrow = 0)), c("variable_id", "expose", "filter"))

for (i in 1:nrow(df_de_raw)) {
  
  variable_id <- df_de_raw[i,2]
  
  raw_string <- df_de_raw[i,1]
  
  #change this for all varaible types
  code_string <- str_split_fixed(raw_string, "s:22:\"data_explorer_settings\"", n = 2)
  
  
  # get the data explorer setting. When set the same word appears twice
  
  de_expose <- "no"
  de_filter <- "no"
  
  data_explorer_expose <- str_count(code_string[1,2], "expose")
  data_explorer_filter <- str_count(code_string[1,2], "filter")
  
  if(data_explorer_expose == 1) {de_expose <- "no"} else {de_expose <- "yes"}
  if(data_explorer_filter == 1) {de_filter <- "no"} else {de_filter <- "yes"}
  
  df_dataexplorer_row <- data.frame("variable_id" = variable_id,
                                    "expose" = de_expose,
                                    "filter" = de_filter)
  
  df_dataexplorer <- rbind(df_dataexplorer, df_dataexplorer_row)
  
}
