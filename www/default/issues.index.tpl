{% extends _layout %}

{% block content %}
	<ul>
  {% for item in items %}
		<li>
			<a href="https://github.com/{{ project.gh_user }}/{{ project.gh_project }}/issues/{{ item.gh_id }}" target="_blank">{{ item.gh_id }}</a>
		</li>
  {% endfor %}
  </ul>

{% endblock %}