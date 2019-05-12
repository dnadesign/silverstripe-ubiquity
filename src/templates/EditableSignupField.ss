<input $AttributesHTML<% if $RightTitle %>aria-describedby="{$Name}_right_title" <% end_if %>/>
<label class="left" for="$ID"><% if $HTMLLabel %>$HTMLLabel.RAW<% else %>$Title<% end_if %></label>
