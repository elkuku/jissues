{% extends "default.tpl" %}

{% block content %}

{{ text._('COM_TRACKER_FILTER_STATUS') }}
	<ul>
		{% for item in items %}
			<li>
				<a href="https://github.com/{{ project.gh_user }}/{{ project.gh_project }}/issues/{{ item.gh_id }}" target="_blank">{{ item.gh_id }}</a>
			</li>
		{% endfor %}
	</ul>
{% endblock %}
