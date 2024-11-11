import sys
import os
from pprint import pprint
from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Core.IFSelect import IFSelect_RetDone, IFSelect_ItemsByEntity

#pprint(sys)
pprint(globals())
pprint(locals())
pprint(object.__dict__)
print("oi")
print(sys.argv[0])
print(sys.orig_argv[0])
print(sys.orig_argv[1])
print(sys.argv[1])
print(sys.argv[2])


#from sys import argv
#script, args = argv[0], argv[1:]

#print("The script is called: ", script)
#for i, arg in enumerate(args):
#    print("Arg {0:d}: {1:s}".format(i, arg))
    
#pathSTEP = sys.argv[1]
#pathSTL = sys.argv[2]

print("oi")
