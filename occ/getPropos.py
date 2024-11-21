#!/usr/bin/env python3

#conda install -c dlr-sc pythonocc-core
#conda install -c dlr-sc/label/dev pythonocc-core

from OCC.Extend.ShapeFactory import (get_aligned_boundingbox, get_oriented_boundingbox)
from OCC.Extend.DataExchange import (read_step_file)
from OCC.Core.GProp import GProp_GProps
#from OCC.Core.BRepGProp import brepgprop_LinearProperties
from OCC.Core.BRepGProp import brepgprop

import sys
import os
import json

path = sys.argv[1]

def print_bounding_boxes(shape):
    # Prints the axis-aligned bounding box data as a tuple of:
    # Center of the AABB
    # Tuple of [x, y, z]
    # Bounding box as a solid
    ##print('Prints the axis-aligned bounding box data as a tuple')
    boundingBox = get_aligned_boundingbox(shape)
    boundingBox = boundingBox[1]
    ##print(get_aligned_boundingbox(shape))
    #print("bounding box x: "+str(boundingBox[0])+" mm")
    #print("bounding box y: "+str(boundingBox[1])+" mm")
    #print("bounding box z: "+str(boundingBox[2])+" mm")

    g1 = GProp_GProps()
    ##brepgprop_LinearProperties(shape, g1)
    brepgprop.LinearProperties(shape, g1)
    g2 = GProp_GProps()
    brepgprop.VolumeProperties(shape, g2)
    #print("Volume: "+str(g2.Mass())+" mm³")
    g3 = GProp_GProps()
    brepgprop.SurfaceProperties(shape, g3)
    #print("Surperfície: "+str(g3.Mass())+" mm²")

    #print("Volume da bounding box: "+str((boundingBox[0]*boundingBox[1]*boundingBox[2]))+" mm³")
    #print("Diferença de volume: "+str((boundingBox[0]*boundingBox[1]*boundingBox[2])-g2.Mass())+" mm³")

    ##result["boundingbox"]["x"] = boundingBox[0]
    result = {
        "volume_peca": g2.Mass(),
        "superficie": g3.Mass(),
        "boundingbox": [boundingBox[0], boundingBox[1], boundingBox[2]]
    }
    json_string = json.dumps(result, indent=4)
    print(json_string)
    ##print("massa")
    ##mass = g1.Mass()
    ##print(mass)

    ##g1.Add(g1, 1)
    ##mass = g1.Mass()
    ##print(mass)

    # https://dev.opencascade.org/doc/refman/html/class_g_prop___g_props.html
    # Returns the mass of the current system. If no density is attached to the components of the current
    # system the returned value corresponds to :

   # the total length of the edges of the current system if this framework retains only linear properties,
   # as is the case for example, when using only the LinearProperties function to combine properties of
   # lines from shapes,
   
   # or the total area of the faces of the current system if this framework retains only
   # surface properties, as is the case for example, when using only the SurfaceProperties function to combine
   # properties of surfaces from shapes,
   
   # or the total volume of the solids of the current system if this
   # framework retains only volume properties, as is the case for example, when using only the VolumeProperties
   # function to combine properties of volumes from solids.

   # Warning A length, an area, or a volume is computed
   # in the current data unit system. The mass of a single object is obtained by multiplying its length, its
   # area or its volume by the given density. You must be consistent with respect to the units used.
    
    # Prints the oriented bounding box data as a tuple of:
    # CBaryCenter of the OBB
    # Tuple of half sizes [x, y, z]
    # Bounding box as a solid
    #print('Prints the oriented bounding box data as a tuple')
    #print(get_oriented_boundingbox(shape))

    # The AABB center can bo obtained as a Tuple of [x, y, z] using:
    # get_aligned_boundingbox(shape)[0].Coord()

    # The OBB rotation can be obtained as a Quaterion of [x, y, z, w] using:
    #quaternion = get_oriented_boundingbox(shape)[2].Location().Transformation().GetRotation()
    #print([quaternion.X(), quaternion.Y(), quaternion.Z(), quaternion.W()])

shp = read_step_file(path)
print_bounding_boxes(shp)
