# set up the libraries

library(tidyr)
library(dplyr)
library(stringr)

# read in the csv file exported from the database with BLOB fields converted to text
# obviously, the query could be run from within an R script if somebody wants to program it.

df_raw <- read.csv("variableExport.csv", header = T, quote = "\"", as.is = T)

# subset for variables that describe the enumeratedDomain
# subset file for type code

df_codes_raw <- filter(df_raw, field_variables_type == "codes")
df_codes_raw <- select(df_codes_raw, variables_data, field_variables_id, field_variables_name, field_variables_label)

#set up the data frame to hold the parsed information
df_key_value <- setNames(data.frame(matrix(ncol = 4, nrow = 0)), c("variable_id", "delta", "code", "definition"))

#pattern when the code is number rather than text
pattern_num <- "i:[[[:punct:]]\\d]+"

#step through each record and parse the code/definition out
for (i in 1:nrow(df_codes_raw)) {
  
  variable_id <- df_codes_raw[i,2]
  
  raw_string <- df_codes_raw[i,1]
  
  #split off the data explorer settings
  code_string <- str_split_fixed(raw_string, "s:22:\"data_explorer_settings\"", n = 2)
  
  code_string_split <- str_split_fixed(code_string[1,1], "s:\\d+:", n = Inf)
  
  # code is number
  if (str_detect(code_string[1,1], pattern_num)) {
    
    o <- 1
    
    for (m in 2:(length(code_string_split)-1)){
    
      code_num_extr1 <- str_extract(code_string_split[1,m], pattern_num)
    
      code_num <- str_extract_all(code_num_extr1, "[-\\d]+")
    
      code_text_split <- str_split_fixed(code_string_split[1,(m+1)], "\"", n = Inf)
      code_text <- code_text_split[1,2]
    
      df_key_value_row <- data.frame("variable_id" = variable_id,
                                     "delta" = o,
                                     "code" = as.character(code_num),
                                     "definition" = code_text)
      df_key_value <- rbind(df_key_value, df_key_value_row)
      
      o <- o + 1
      
    }
      
      
  }
  
  # code is text
  else {
    
    read_row_nums <- seq(3, length(code_string_split), 2)
    
    p <- 1
    
    for (n in read_row_nums){
    
      
      code_text_split <- str_split_fixed(code_string_split[1,n], "\"", n = Inf)
      code_text <- code_text_split[1,2]
      
      definition_text_split <- str_split_fixed(code_string_split[1,(n+1)], "\"", n = Inf)
      definition_text <- definition_text_split[1,2]

      df_key_value_row <- data.frame("variable_id" = variable_id,
                                     "delta" = p,
                                     "code" = code_text,
                                     "definition" = definition_text)
      
      df_key_value <- rbind(df_key_value, df_key_value_row)
      
      p <- p + 1
      

    }
    
  }

}

# writing this file is not needed but good to look at
# write.csv(df_key_value, file = "code_definitions.csv", row.names = F)


# build csv file to generate nodes of type 'variable_codes'

upload_code_def <- select(df_codes_raw, variable_id = field_variables_id, field_variables_name, field_variables_label)

#if you want to control nid you need to know what you are doing.
#if two uploads have the same nid the records will just be overwritten
#I am using 1 - 5000 for code definitions

upload_code_def$nid <- 1:nrow(upload_code_def)

upload_code_def$type <- "variable_codes"
upload_code_def$langcode <- "en"
upload_code_def$status <- 1

#make a meaningful title so they can be reused later
upload_code_def <- mutate(upload_code_def, title = ifelse(nchar(field_variables_label) > 1, field_variables_label, field_variables_name))
upload_code_def$title <- paste("Code definition for", upload_code_def$title)

upload_code_def$uid <- 1
upload_code_def$promote <- 0
upload_code_def$sticky <- 0

#take not needed columns out

upload_code_def <- select(upload_code_def, variable_id, nid, type, langcode, status, title, uid, promote, sticky)
# save file that will get picked up by YML script to generate nodes

write.csv(upload_code_def, file = "upload_code_def_node.csv", row.names = F)

# stop here!!!!!!
# run the command 'drush migrate:import deims_csv_varcodedef' on the commandline in the webroot
# get the nid vid mapped for these new nodes by running the query exportVariableCodeIDs.sql and save the export as nid_vid_mapping.csv

nid_vid_mapping <- read.csv("nid_vid_mapping.csv", header = T, as.is = T)

upload_values <- inner_join(upload_code_def, df_key_value, by="variable_id")
upload_values <- select(upload_values, bundle = type, link_id = nid, langcode, delta, field_variable_code_definition_value = definition, field_variable_code_definition_key = code)
upload_values <- inner_join(upload_values, nid_vid_mapping, by="link_id")
upload_values <- select(upload_values, entity_id, vid, bundle, langcode, delta, field_variable_code_definition_value, field_variable_code_definition_key)

# save file to manually upload into the D8 table 'node__field_variable_code_definition'
# again, this could be run from R if somebody wants to program it

write.csv(upload_values, file = "upload_code_def_values.csv", row.names = F)

# add the code node id to the raw data file for future reference
df_code_id <- select(upload_code_def, field_variables_id = variable_id, code_id = nid)
df_raw <- left_join(df_raw, df_code_id, by = "field_variables_id")

write.csv(df_raw, file = "variableExport_codeID.csv", row.names = F)
