import { EventEmitter} from "events";

import dispatcher from '../dispatcher.jsx';

import request from '../API.jsx';

//generates a matrix, filled with -1 of size x*y
function getEmptyTable(x,y){
	var a = [], b;
	while (a.push(b = []) <= x) while (b.push(-1) < y);
	a.pop();
	return {width : x, height : y, table : a}
}


class ClassStore extends EventEmitter{
	constructor(){
		super();
		this.unused = [];
		this.used = [];
		this.classPosition = {
		}; 

		this.setMaxListeners(100);

		this.table = getEmptyTable(6,4);

		this.loadLessons(1);

		this.colClasses = ['8E','9E','10E','11E'];
	}

	loadLessons(project_id){
		request('getLessons', {'project_id':project_id}).then(res=>{	
			//this.unused = res.data;
			//
			const dataLen = res.data.length;
			for (let i =0; i< dataLen; i++){
				this.unused.push({
					id : i,
					db_id : parseInt(res.data[i].id),
					grade : res.data[i].grade_number + res.data[i].grade_name,
					name : res.data[i].lesson_name,
					teacher : res.data[i].name,
					color : 'red'
				});
				this.classPosition[this.unused[i].id] = {isUsed : false, index : i, x : -1, y : -1};


			}
			this.emit('change');
		});
	}

	getUnused(){
		return this.unused;
	}

	getLenUnused(){
		return this.unused.length;
	}

	setUnused(arr){
		this.unused = arr;
		this.emit('change');
	}


	canDrop(id, x,y){
		
		const lesson = this.getClassByID(id);

		if (this.colClasses[y] != lesson.grade){
			return false;
		}
		for (let i=0; i< this.table.height; i++){
			
			if (i!=y && this.table.table[x][i] != -1 && this.getClassByID( this.table.table[x][i]).teacher == lesson.teacher){
				return false;
			}
		}

		return true;
	}

	moveUnusedItem(from,to){
		const minInd = Math.min(from,to);
		const maxInd = Math.max(from,to);
		if (maxInd-minInd > 1){
			if (from < to){

				const tmp = this.unused[minInd];
				for (let i =minInd+1; i<=maxInd; i+=1 ){
					this.unused[i-1] = this.unused[i];
					this.classPosition[this.unused[i-1].id].index = i-1;
				}
				this.unused[maxInd] = tmp; 
				this.classPosition[tmp.id].index = maxInd;
			} else {
				const tmp = this.unused[maxInd];
				for (let i =maxInd-1; i>=minInd; i-=1 ){
					this.unused[i+1] = this.unused[i];
					this.classPosition[this.unused[i+1].id].index = i+1;
				}
				this.unused[minInd] = tmp; 
				this.classPosition[tmp.id].index = minInd;
			}
		} else {

			const tmp = this.unused[minInd];
			this.unused[minInd] = this.unused[maxInd];
			this.classPosition[this.unused[minInd].id].index = minInd;

			this.unused[maxInd] = tmp;
			this.classPosition[this.unused[maxInd].id].index = maxInd;

		}
		this.emit('change');

	}

	getClassByID(id){
		return this.getClassByPos(this.classPosition[id]);
	}

	getClassByPos(pos){
		return pos.isUsed ? this.used[pos.index] : this.unused[pos.index];
	}

	setClassByPos(pos,val,emit = true){
		if (pos.isUsed){
			this.used[pos.index] = val;	
			this.table.table[pos.x][pos.y] = val.id;
		} else {
			this.unused[pos.index] = val;
		}
		if (emit){
			this.emit('change');
		}
	}
/*
	swapByPos(pos1,pos2){
		
	}
*/
	swapByID(id1,id2){
		const pos1 = this.classPosition[id1];
		const pos2 = this.classPosition[id2];
 		
 		const tmp = this.getClassByPos(pos1);

		this.setClassByPos(pos1, this.getClassByPos(pos2),false);
		this.setClassByPos(pos2, tmp,false);
		
		this.classPosition[id1] = pos2;
		this.classPosition[id2] = pos1;

		this.emit('change');
	}

	//sets class with id=id to the grid with coordinates x,y
	//supposes, that (x,y) is empty
	setUsed(id,x,y){
		const pos = this.classPosition[id];
		if (this.table.table[x][y] != -1){
			console.error('trying to set in not empty grid cell!',this.table.table[x][y]);
			return;
		}

		if (pos.isUsed){
			this.table.table[x][y] = id;
			this.table.table[pos.x][pos.y] = -1;
			this.classPosition[id].x = x;
			this.classPosition[id].y = y;
		} else {
			this.table.table[x][y] = id;
			this.classPosition[id] = {isUsed : true, index: this.used.length, x: x, y: y};
			for (let i = pos.index+1; i < this.unused.length; i++){
				this.classPosition[this.unused[i].id].index = i-1;
			}

			this.used.push(this.unused[pos.index]);
			this.unused.splice(pos.index,1);			
		}
		this.emit('change');
	}
	
	setToUnused(id){
		const pos = this.classPosition[id];

		if (pos.isUsed){
			this.table.table[pos.x][pos.y] = -1;
			this.classPosition[id] = {isUsed : false, index: this.unused.length, x: -1, y: -1};
			
			for (let i = pos.index+1; i < this.used.length; i++){
				this.classPosition[this.used[i].id].index = i-1;
			}

			this.unused.push(this.used[pos.index]);
			this.used.splice(pos.index,1);
			this.emit('change');
		}
	}

	handleActions(action){

		switch (action.type) {
			case "SWAP_UNUSED_CLASS_BY_INDEX":
				this.moveUnusedItem(action.i1, action.i2);
				break;
			case "SWAP_CLASS_BY_ID":
				this.swapByID(action.i1, action.i2);
				break;
			case "MOVE_TO_USED":
				this.setUsed(action.id, action.x,action.y);
				break;
			case "MOVE_TO_UNUSED":
				this.setToUnused(action.id);
				break;
			

			default:
				// statements_def
				break;
		}
	}
}

const classStore = new ClassStore; 

dispatcher.register(classStore.handleActions.bind(classStore));

export default classStore;