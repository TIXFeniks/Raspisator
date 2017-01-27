import React from "react";
import FormData from 'react-form-data';
import DatePicker from 'react-bootstrap-date-picker';
import request from '../API.jsx';
import { hashHistory } from 'react-router';
import {Form, FormControl, Alert, ButtonGroup ,Button, FormGroup, Col, Row, ControlLabel} from 'react-bootstrap';
import renderAlert from './alert.jsx';
import ScheduleGrid from './scheduleGrid.jsx';
import scheduleStore from '../stores/scheduleStore.jsx';

export default class NewProject extends(React.Component){
	constructor(props){
		super(props);
		this.formData = {
			s_id:-1,
			start:'',
			finish:''
		}
		this.updateFormData = FormData.updateFormData.bind(this);
		this.setFormData = FormData.setFormData.bind(this);
		this.state = {schools:[], alertMessage:''};
	}

	componentWillMount(){
		scheduleStore.getSchools().then(res=>{this.setState({schools : res.data, alertMessage:''})});
		
	}

	//validates data, returns false if it's ok or an error message else 
	validateFormData(data){
		if (data.s_id == "-1") return 'Вы должны выбрать школу';
		if (data.start == '') return 'Вы должны указать дату начала';
		if (data.finish == '') return 'Вы должны указать дату конца';
		if (Date.parse(data.finish) <= Date.parse(data.start)) return 'Вы дата конца не может быть раньше даты начала';
		
		return false;
	}

	handleSubmit(event){
		const dat = this.formData;
		console.log(dat);
		const validation = this.validateFormData(dat);

		if (validation){
			this.setState({schools:this.state.schools, alertMessage: validation})
		} else {
			scheduleStore.setDates(dat.start, dat.finish, dat.s_id);
			this.setState({schools:this.state.schools, alertMessage:''});
		}
	}

	render(){
		let a =0;

		const dayLabels = ['Вс','Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
		const monthLabels = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

		const schoolOptions = this.state.schools.map((school,index)=>{return (
				<option value={school.id} key={index}>{school.name}</option>
			)});

		return (//TODO: rewrite it with react-bootstrap
			<div>
				<h1>Личное расписание</h1>
				<Row>
					<Form onChange={this.updateFormData} method="POST" acceptCharset="utf-8" action="http://localhost/var/www/html/Raspisator/API/register.php">
						<Col md={4} xs={10}>
							<FormGroup>
								<ControlLabel>Школа</ControlLabel>
								<FormControl placeholder="select" value={this.formData.s_id} componentClass="select" onChange={(e)=> {this.handleSubmit(e)}} type="select" name="s_id">
									<option value="-1">---</option>
									{schoolOptions}
								</FormControl>
							</FormGroup>
						</Col>
						<Col md={4} xs={10}>
							<FormGroup>
								<ControlLabel>Дата начала расписания</ControlLabel>
								<DatePicker value={this.formData.start} dateFormat="DD-MM-YYYY" dayLabels={ dayLabels} monthLabels = {monthLabels} onChange={(e)=>{this.formData.start = e.slice(0,10); this.handleSubmit(undefined)}} name='start'/>
							</FormGroup>
						</Col>
						<Col md={4} xs={10}>
							<FormGroup>
								<ControlLabel>Дата конца расписания</ControlLabel>
								<DatePicker dateFormat="DD-MM-YYYY" dayLabels={ dayLabels} value={this.formData.finish} monthLabels = {monthLabels} onChange={(e)=>{this.formData.finish = e.slice(0,10); this.handleSubmit(undefined)}} name='finish'/>
							</FormGroup>
						</Col>
						{renderAlert(this.state.alertMessage)}
					</Form>
				</Row>
				<ScheduleGrid />
				<ButtonGroup className="fixed-buttons">
					<Button className="btn-success save-btn" onClick={()=>scheduleStore.save()}> Сохранить </ Button>
				</ButtonGroup>

			</div>
			);
	}
}