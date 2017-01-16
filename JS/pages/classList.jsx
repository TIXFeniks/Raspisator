import React from "react";
import Class from './class.jsx';
import update from 'react/lib/update';
import { DropTarget } from 'react-dnd';
import { findDOMNode } from 'react-dom';
import ItemTypes from '../ItemTypes.jsx';
import * as ClassActions from '../actions/classActions.jsx';
import classStore from '../stores/classStore.jsx';



const cardTarget = {
    drop(props, monitor, component){
        const dragID  = monitor.getItem().id;
        if (classStore.classPosition[dragID].isUsed){
            ClassActions.setUnused(dragID);
        }
    },

    hover(props, monitor, component) {

        const dragID  = monitor.getItem().id;
        if (! classStore.classPosition[dragID].isUsed){
            const dragIndex = monitor.getItem().index;
            const thisRect = findDOMNode(component).children[0].getBoundingClientRect();
            

            // Determine mouse position
            const clientOffset = monitor.getClientOffset();

            const numClasses = component.poses.length;

            // get class height to to count the changes amount
            const classHeight = (r=>r.bottom - r.top)(thisRect)/numClasses;
            //get index of hovered class
            let hoverIndex = Math.floor((clientOffset.y-thisRect.top)/ classHeight);
            
            // Don't replace items with themselves
            if (dragIndex === hoverIndex) {
              return;
            }



            // Determine rectangle on screen
            const hoverBoundingRect = {
            	top: thisRect.top + hoverIndex*classHeight,
            	bottom: thisRect.top + (hoverIndex+1)*classHeight
            };

            // Get vertical middle
            const hoverMiddleY = (hoverBoundingRect.bottom - hoverBoundingRect.top) / 2;

            // Get pixels to the top
            const hoverClientY = clientOffset.y - hoverBoundingRect.top;

            //if it's out of bounds don't do anything
            if (hoverIndex < 0 || hoverIndex >= numClasses) return;

            // Only perform the move when the mouse has crossed half of the items height
            // When dragging downwards, only move when the cursor is below 50%
            // When dragging upwards, only move when the cursor is above 50%

            // Dragging downwards
            if (dragIndex == hoverIndex - 1 && hoverClientY < hoverMiddleY) {
                return;
            }

            // Dragging upwards
            if (dragIndex == hoverIndex + 1 && hoverClientY > hoverMiddleY) {
                return;
            }

            // Time to actually perform the action
            
            const hover_db_id = classStore.unused[component.poses[dragIndex]].db_id;
            if (component.poses[dragIndex]< component.poses[hoverIndex]){
                let i =0;
                while(i < component.poses[hoverIndex]){
                    if (classStore.unused[i].db_id == hover_db_id){
                        ClassActions.swapByIndex(i, component.poses[hoverIndex], false);
                        i = 0;
                        continue;
                    }
                    i++;
                }
            } else {
                ClassActions.swapByIndex(component.poses[dragIndex], component.poses[hoverIndex],false);
            }
            
            monitor.getItem().index = hoverIndex;
            //console.log(this);
        }
    }
};

@DropTarget(ItemTypes.CLASS, cardTarget, connect => ({
  connectDropTarget: connect.dropTarget()
}))
export default class ClassList extends(React.Component){
	constructor(props){
		super(props);
		this.rerender = this.rerender.bind(this);
	}

	rerender(){
		this.setState({});
	}

	componentWillMount(){
		classStore.on('change', this.rerender)
	}

	componentWillUnmount(){
		classStore.removeListener('change', this.rerender );
	}

	render(){
        const { grade, used } = this.props;


		const unused = classStore.getLessons(used,grade);

        let unique = {};
        for (let i =0; i< unused.length; i++){
            const les = unused[i];
            if (les.db_id in unique)
                unique[les.db_id].count+=1
            else
                unique[les.db_id] = {c:les, count: 1, firstPos: i};
        }


        let classes = [];
        let poses = []
        for (let i =0; i< unused.length; i++){
            const db_id = unused[i].db_id;
            const {c, count, firstPos} = unique[db_id];
            if (firstPos!=i) continue;
            classes.push(
                    <Class name={c.name} id={c.id} index={classes.length} color={c.color} teacher={c.teacher} grade={c.grade} amount={count} key={c.id}/>
                )
            poses.push(firstPos);
        }
		/*const classes = unused.map((c,index) =>
				<Class name={c.name} id={c.id} index={index} color={c.color} teacher={c.teacher} grade={c.grade} key={c.id}/>
			)
        */

        this.poses = poses;

		const {connectDropTarget} = this.props;
		return connectDropTarget( 
                <div className="class-list">
            			<div>
            				{classes}
            			</div>
            			
                </div>
            );
	}
}
